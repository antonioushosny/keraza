<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBadge extends Model
{
    protected $fillable = ['student_season_enrollment_id', 'badge_id', 'awarded_at'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentSeasonEnrollment::class, 'student_season_enrollment_id');
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }
}
