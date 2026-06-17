<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAttendance extends Model
{
    protected $fillable = ['activity_attendance_session_id', 'activity_enrollment_id', 'status', 'notes'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ActivityAttendanceSession::class, 'activity_attendance_session_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ActivityEnrollment::class, 'activity_enrollment_id');
    }
}
