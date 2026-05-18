<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.api');
    }

    public function index(Request $request, $team_id = null)
    {
        $user = auth()->user();
        
        if ($team_id) {
            $team = Team::findOrFail($team_id);
            $this->authorizeTeamAccess($team);
            $query = $team->tasks();
        } else {
            if ($user->role === 'admin') {
                $query = Task::query();
            } elseif ($user->role === 'manager') {
                $teamIds = $user->teams()->pluck('teams.id');
                $query = Task::whereIn('team_id', $teamIds);
            } else {
                $query = Task::where('assigned_to', $user->id);
            }
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('date_from')) {
            $query->whereDate('due_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('due_date', '<=', $request->date_to);
        }

        $tasks = $query->with(['assignedUser', 'creator', 'team'])
                      ->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 15));

        return response()->json($tasks);
    }

    public function store(Request $request, $team_id)
    {
        $team = Team::findOrFail($team_id);
        $this->authorizeTaskCreation($team);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify assigned user is in the team
        $assignedUser = User::find($request->assigned_to);
        if (!$team->isMember($assignedUser)) {
            return response()->json(['message' => 'Assigned user must be a team member'], 422);
        }

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'pending',
            'assigned_to' => $request->assigned_to,
            'created_by' => auth()->id(),
            'team_id' => $team_id,
            'due_date' => $request->due_date
        ]);

        $task->recordHistory(auth()->id(), 'created');

        // Send notification via Node.js service
        $this->sendNotification($task, 'assigned');

        return response()->json($task, 201);
    }

    public function show($id)
    {
        $task = Task::with(['assignedUser', 'creator', 'team', 'histories.user'])
                   ->findOrFail($id);
        
        $this->authorizeTaskAccess($task);

        return response()->json($task);
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $this->authorizeTaskUpdate($task);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high',
            'assigned_to' => 'sometimes|exists:users,id',
            'due_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldData = $task->toArray();
        $task->update($request->only(['title', 'description', 'priority', 'assigned_to', 'due_date']));
        
        // Track changes
        $changes = array_diff_assoc($task->toArray(), $oldData);
        if (!empty($changes)) {
            $task->recordHistory(auth()->id(), 'updated', $changes);
        }

        // Send notification if assigned user changed
        if ($request->has('assigned_to') && $request->assigned_to != $oldData['assigned_to']) {
            $this->sendNotification($task, 'assigned');
        }

        return response()->json($task);
    }

    public function updateStatus(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $this->authorizeTaskAccess($task);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$task->canTransitionTo($request->status)) {
            return response()->json([
                'message' => "Invalid status transition from {$task->status} to {$request->status}"
            ], 422);
        }

        $oldStatus = $task->status;
        $task->status = $request->status;
        $task->save();

        $task->recordHistory(auth()->id(), 'status_changed', [
            'from' => $oldStatus,
            'to' => $request->status
        ]);

        // Send notification for status change
        $this->sendNotification($task, 'status_changed');

        return response()->json($task);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        
        if (auth()->user()->role !== 'admin' && $task->created_by !== auth()->id()) {
            return response()->json(['message' => 'Only task creator or admin can delete tasks'], 403);
        }

        $task->recordHistory(auth()->id(), 'deleted');
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function archive($id)
    {
        $task = Task::findOrFail($id);
        
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Only admins can archive tasks'], 403);
        }

        if ($task->status !== 'cancelled') {
            return response()->json(['message' => 'Only cancelled tasks can be archived'], 422);
        }

        $task->forceDelete();

        return response()->json(['message' => 'Task archived successfully']);
    }

    private function authorizeTeamAccess($team)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            return;
        }
        
        if ($user->role === 'manager' && !$team->isMember($user)) {
            abort(403, 'You do not have access to this team');
        }
        
        if ($user->role === 'team_member' && !$team->isMember($user)) {
            abort(403, 'You are not a member of this team');
        }
    }

    private function authorizeTaskCreation($team)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin' || $user->role === 'manager') {
            if (!$team->isMember($user)) {
                abort(403, 'You must be a team member to create tasks');
            }
        } else {
            abort(403, 'Only admins and managers can create tasks');
        }
    }

    private function authorizeTaskAccess($task)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            return;
        }
        
        if ($user->role === 'manager') {
            if (!$task->team->isMember($user)) {
                abort(403, 'You do not have access to this task');
            }
        } else {
            if ($task->assigned_to !== $user->id) {
                abort(403, 'You can only view your own tasks');
            }
        }
    }

    private function authorizeTaskUpdate($task)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            return;
        }
        
        if ($user->role === 'manager') {
            if (!$task->team->isMember($user)) {
                abort(403, 'You can only update tasks in your team');
            }
        } else {
            if ($task->assigned_to !== $user->id) {
                abort(403, 'You can only update your own tasks');
            }
        }
    }

    private function sendNotification($task, $eventType)
    {
        try {
            $nodeServiceUrl = config('app.node_service_url', 'http://localhost:3001');
            
            Http::post($nodeServiceUrl . '/api/notifications/send', [
                'task_id' => $task->id,
                'user_id' => $task->assigned_to,
                'event_type' => $eventType,
                'details' => [
                    'task_title' => $task->title,
                    'task_status' => $task->status,
                    'assigned_by' => auth()->user()->name
                ]
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send notification: ' . $e->getMessage());
        }
    }
}