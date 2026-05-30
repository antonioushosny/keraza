<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehaviorLog extends Model
{
    protected $fillable = ['student_season_enrollment_id', 'type', 'points', 'reason', 'created_by'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentSeasonEnrollment::class, 'student_season_enrollment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
