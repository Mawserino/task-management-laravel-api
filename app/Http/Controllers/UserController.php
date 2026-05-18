<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.api');
        $this->middleware('role:admin,manager')->except(['show']);
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // Managers can only see team members
        if (auth()->user()->role === 'manager') {
            $teamIds = auth()->user()->teams()->pluck('teams.id');
            $query->whereHas('teams', function($q) use ($teamIds) {
                $q->whereIn('teams.id', $teamIds);
            })->orWhere('id', auth()->id());
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,manager,team_member'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Managers can only create team members
        if (auth()->user()->role === 'manager' && $request->role !== 'team_member') {
            return response()->json(['message' => 'Managers can only create team members'], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json($user, 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        
        // Check authorization
        if (auth()->user()->role !== 'admin' && 
            auth()->user()->role !== 'manager' && 
            auth()->id() != $id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|in:admin,manager,team_member'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check authorization
        if (auth()->user()->role !== 'admin') {
            if (auth()->user()->role === 'manager' && $user->role !== 'team_member') {
                return response()->json(['message' => 'Managers can only edit team members'], 403);
            }
            if (auth()->user()->role === 'team_member' && auth()->id() != $id) {
                return response()->json(['message' => 'You can only edit your own profile'], 403);
            }
        }

        $user->update($request->only(['name', 'email', 'role']));

        return response()->json($user);
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Only admins can toggle user status'], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot deactivate yourself'], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(['message' => 'Status updated', 'is_active' => $user->is_active]);
    }
}