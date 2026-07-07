<?php

namespace App\Filament\Resources\ActivityEvaluationResource\Pages;

use App\Filament\Resources\ActivityEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityEvaluation extends EditRecord
{
    protected static string $resource = ActivityEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $evaluation = $this->record;
        
        $enrollmentIds = \App\Models\ActivityEnrollment::where('activity_id', $evaluation->activity_id)
            ->pluck('id')
            ->toArray();

        $existingIds = \App\Models\ActivityScore::where('activity_evaluation_id', $evaluation->id)
            ->pluck('activity_enrollment_id')
            ->toArray();

        $missingIds = array_diff($enrollmentIds, $existingIds);

        foreach ($missingIds as $aeId) {
            \App\Models\ActivityScore::firstOrCreate([
                'activity_evaluation_id' => $evaluation->id,
                'activity_enrollment_id' => $aeId,
            ], [
                'raw_score' => 0,
            ]);
        }

        return $data;
    }
}
