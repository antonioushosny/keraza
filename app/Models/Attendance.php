<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';
    protected $fillable = ['attendance_session_id', 'student_season_enrollment_id', 'status', 'points', 'notes'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentSeasonEnrollment::class, 'student_season_enrollment_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }
}
