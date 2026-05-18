<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'status', 'priority', 
        'assigned_to', 'created_by', 'team_id', 'due_date'
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    const STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];
    const PRIORITIES = ['low', 'medium', 'high'];

    const STATUS_TRANSITIONS = [
        'pending' => ['in_progress', 'cancelled'],
        'in_progress' => ['pending', 'completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function histories()
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function canTransitionTo($newStatus)
    {
        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? []);
    }

    public function recordHistory($userId, $action, $changes = null)
    {
        return $this->histories()->create([
            'user_id' => $userId,
            'action' => $action,
            'changes' => $changes ? json_encode($changes) : null,
        ]);
    }
}