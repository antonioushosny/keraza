<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\StudentSeasonEnrollment;

/**
 * Service to ensure attendance records are in sync with current enrollments.
 *
 * When a new student is added to a class after an attendance session has been
 * created, the session will not have an Attendance record for that student.
 * This service creates missing records with a default status of 'absent'.
 */
class AttendanceSyncService
{
    /**
     * Synchronize attendance records for the given session.
     *
     * @param AttendanceSession $session
     * @return void
     */
    public function syncSessionAttendances(AttendanceSession $session): void
    {
        // Retrieve all enrollment IDs for the class/season of the session.
        $enrollmentIds = StudentSeasonEnrollment::where('class_id', $session->class_id)
            ->where('season_id', $session->season_id)
            ->pluck('id')
            ->toArray();

        // Existing attendance enrollment IDs for this session.
        $existingIds = Attendance::where('attendance_session_id', $session->id)
            ->pluck('student_season_enrollment_id')
            ->toArray();

        // Determine which enrollments are missing attendance records.
        $missingIds = array_diff($enrollmentIds, $existingIds);

        // Create a missing attendance record for each enrollment.
        foreach ($missingIds as $enrollmentId) {
            Attendance::create([
                'attendance_session_id' => $session->id,
                'student_season_enrollment_id' => $enrollmentId,
                'status' => 'absent',
            ]);
        }
    }
}
