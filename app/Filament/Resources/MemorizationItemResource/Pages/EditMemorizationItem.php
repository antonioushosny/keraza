<?php

namespace App\Filament\Resources\MemorizationItemResource\Pages;

use App\Filament\Resources\MemorizationItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMemorizationItem extends EditRecord
{
    protected static string $resource = MemorizationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $item = $this->record;
        
        $enrollmentIds = \App\Models\StudentSeasonEnrollment::where('class_id', $item->class_id)
            ->where('season_id', $item->season_id)
            ->pluck('id')
            ->toArray();

        $existingIds = \App\Models\MemorizationScore::where('memorization_item_id', $item->id)
            ->pluck('student_season_enrollment_id')
            ->toArray();

        $missingIds = array_diff($enrollmentIds, $existingIds);

        foreach ($missingIds as $enrollmentId) {
            \App\Models\MemorizationScore::firstOrCreate([
                'memorization_item_id' => $item->id,
                'student_season_enrollment_id' => $enrollmentId,
            ], [
                'score' => 0,
                'accuracy' => 100,
            ]);
        }

        return $data;
    }
}
