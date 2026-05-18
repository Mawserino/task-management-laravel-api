<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.api');
        $this->middleware('role:admin,manager');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            $teams = Team::with(['creator', 'members'])->paginate($request->get('per_page', 15));
        } else {
            $teams = $user->teams()->with(['creator', 'members'])->paginate($request->get('per_page', 15));
        }

        return response()->json($teams);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:teams'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $team = Team::create([
            'name' => $request->name,
            'created_by' => auth()->id()
        ]);

        // Add creator as team lead
        $team->members()->attach(auth()->id(), ['role' => 'lead']);

        return response()->json($team, 201);
    }

    public function show($id)
    {
        $team = Team::with(['creator', 'members'])->findOrFail($id);
        
        $this->authorizeTeamAccess($team);

        return response()->json($team);
    }

    public function addMember(Request $request, $id)
    {
        $team = Team::findOrFail($id);
        $this->authorizeMemberManagement($team);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'sometimes|in:lead,member'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::find($request->user_id);
        
        // Check if user is already a member
        if ($team->isMember($user)) {
            return response()->json(['message' => 'User is already a team member'], 422);
        }

        $team->members()->attach($request->user_id, [
            'role' => $request->get('role', 'member')
        ]);

        return response()->json(['message' => 'Member added successfully']);
    }

    public function removeMember($id, $userId)
    {
        $team = Team::findOrFail($id);
        $this->authorizeMemberManagement($team);

        $user = User::findOrFail($userId);
        
        // Cannot remove team lead if they're the only lead
        $leads = $team->members()->wherePivot('role', 'lead')->count();
        $isLead = $team->members()->where('user_id', $userId)->first()->pivot->role === 'lead';
        
        if ($isLead && $leads === 1) {
            return response()->json(['message' => 'Cannot remove the only team lead'], 422);
        }

        $team->members()->detach($userId);

        return response()->json(['message' => 'Member removed successfully']);
    }

    private function authorizeTeamAccess($team)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            return;
        }
        
        if (!$team->isMember($user)) {
            abort(403, 'You do not have access to this team');
        }
    }

    private function authorizeMemberManagement($team)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            return;
        }
        
        if (!$team->isLead($user)) {
            abort(403, 'Only team leads can manage members');
        }
    }
}