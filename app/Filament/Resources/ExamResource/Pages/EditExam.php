<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $exam = $this->record;
        
        $enrollmentIds = \App\Models\StudentSeasonEnrollment::where('class_id', $exam->class_id)
            ->where('season_id', $exam->season_id)
            ->pluck('id')
            ->toArray();

        $existingIds = \App\Models\ExamScore::where('exam_id', $exam->id)
            ->pluck('student_season_enrollment_id')
            ->toArray();

        $missingIds = array_diff($enrollmentIds, $existingIds);

        foreach ($missingIds as $enrollmentId) {
            \App\Models\ExamScore::firstOrCreate([
                'exam_id' => $exam->id,
                'student_season_enrollment_id' => $enrollmentId,
            ], [
                'score' => 0,
            ]);
        }

        return $data;
    }
}
