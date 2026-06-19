<?php

namespace App\Filament\Resources\ActivityAttendanceSessionResource\Pages;

use App\Filament\Resources\ActivityAttendanceSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityAttendanceSession extends EditRecord
{
    protected static string $resource = ActivityAttendanceSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $session = $this->record;
        
        // Get all qualified enrollments for the activity
        $enrollmentIds = \App\Models\ActivityEnrollment::where('activity_id', $session->activity_id)
            // ->where('status', 'qualified')
            ->pluck('id')
            ->toArray();

        // Existing attendances
        $existingIds = \App\Models\ActivityAttendance::where('activity_attendance_session_id', $session->id)
            ->pluck('activity_enrollment_id')
            ->toArray();

        $missingIds = array_diff($enrollmentIds, $existingIds);

        foreach ($missingIds as $enrollmentId) {
            \App\Models\ActivityAttendance::firstOrCreate([
                'activity_attendance_session_id' => $session->id,
                'activity_enrollment_id' => $enrollmentId,
            ], [
                'status' => 'absent',
            ]);
        }

        return $data;
    }
}
