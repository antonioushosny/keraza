<?php

namespace App\Filament\Resources\ActivityTaskResource\Pages;

use App\Filament\Resources\ActivityTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityTask extends EditRecord
{
    protected static string $resource = ActivityTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $task = $this->record;
        
        $enrollmentIds = \App\Models\ActivityEnrollment::where('activity_id', $task->activity_id)
            ->where('status', 'qualified')
            ->pluck('id')
            ->toArray();

        $existingIds = \App\Models\ActivityTaskScore::where('activity_task_id', $task->id)
            ->pluck('activity_enrollment_id')
            ->toArray();

        $missingIds = array_diff($enrollmentIds, $existingIds);

        foreach ($missingIds as $enrollmentId) {
            \App\Models\ActivityTaskScore::firstOrCreate([
                'activity_task_id' => $task->id,
                'activity_enrollment_id' => $enrollmentId,
            ], [
                'score' => 0,
            ]);
        }

        return $data;
    }
}
