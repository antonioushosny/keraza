<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $activeSeason = \App\Models\Season::active();
        if (!$activeSeason) {
            throw new \Exception('لا يوجد موسم نشط حالياً.');
        }

        $enrollment = \App\Models\StudentSeasonEnrollment::findOrFail($data['student_season_enrollment_id']);
        
        // Find or create AttendanceSession
        $session = \App\Models\AttendanceSession::firstOrCreate([
            'season_id' => $activeSeason->id,
            'class_id' => $enrollment->class_id,
            'date' => $data['date'],
        ], [
            'notes' => 'حضور فردي مضاف يدوياً',
        ]);

        // Check if attendance already exists for this enrollment and session to update it instead of creating duplicates
        $existing = \App\Models\Attendance::where('attendance_session_id', $session->id)
            ->where('student_season_enrollment_id', $enrollment->id)
            ->first();

        if ($existing) {
            $existing->delete();
        }

        $data['attendance_session_id'] = $session->id;
        unset($data['date']);
        unset($data['class_id']);

        return $data;
    }
}
