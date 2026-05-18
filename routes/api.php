<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TaskController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth.api'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // User routes
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{id}/status', [UserController::class, 'toggleStatus']);
    
    // Team routes
    Route::apiResource('teams', TeamController::class);
    Route::post('/teams/{id}/members', [TeamController::class, 'addMember']);
    Route::delete('/teams/{id}/members/{userId}', [TeamController::class, 'removeMember']);
    
    // Task routes (nested under teams)
    Route::get('/teams/{team_id}/tasks', [TaskController::class, 'index']);
    Route::post('/teams/{team_id}/tasks', [TaskController::class, 'store']);
    
    // Task routes
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::patch('/tasks/{id}', [TaskController::class, 'update']);
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    Route::delete('/tasks/{id}/archive', [TaskController::class, 'archive']);
});