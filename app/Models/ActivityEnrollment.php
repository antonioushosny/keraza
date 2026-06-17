<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityEnrollment extends Model
{
    protected $fillable = ['student_season_enrollment_id', 'activity_id', 'status'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentSeasonEnrollment::class, 'student_season_enrollment_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ActivityScore::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ActivityAttendance::class, 'activity_enrollment_id');
    }

    public function taskScores(): HasMany
    {
        return $this->hasMany(ActivityTaskScore::class, 'activity_enrollment_id');
    }
}
