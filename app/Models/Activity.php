<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $fillable = [
        'type_id', 
        'season_id', 
        'title', 
        'min_score_to_qualify',
        'weight_attendance',
        'weight_tasks',
        'weight_evaluation'
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'type_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ActivityEnrollment::class);
    }

    public function supervisors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_supervisor', 'activity_id', 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ActivityTask::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(ActivityAttendanceSession::class);
    }
}
