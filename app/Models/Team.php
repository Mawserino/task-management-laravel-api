<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'created_by'];

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members')->withPivot('role');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function isMember(User $user)
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isLead(User $user)
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member && $member->pivot->role === 'lead';
    }
}