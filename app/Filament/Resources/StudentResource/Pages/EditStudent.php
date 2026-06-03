<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    public ?int $selectedClassId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedClassId = isset($data['class_id']) ? intval($data['class_id']) : null;
        unset($data['class_id']);

        $phone = $data['parent_phone'] ?? null;
        $parentName = $data['parent_name'] ?? null;

        if ($phone) {
            $parent = \App\Models\User::createOrGetParent($phone, $parentName, $data['full_name']);

            $data['parent_id'] = $parent->id;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $student = $this->record;

        $activeSeason = \App\Models\Season::active();
        if ($activeSeason && $this->selectedClassId) {
            \App\Models\StudentSeasonEnrollment::updateOrCreate([
                'student_id' => $student->id,
                'season_id' => $activeSeason->id,
            ], [
                'class_id' => $this->selectedClassId,
            ]);
        }
    }
}
