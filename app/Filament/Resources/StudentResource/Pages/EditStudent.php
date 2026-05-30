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
            $parent = \App\Models\User::where('phone', $phone)->first();
            if (!$parent) {
                $parent = \App\Models\User::create([
                    'name' => $parentName ?: ('ولي أمر ' . $data['full_name']),
                    'phone' => $phone,
                    'password' => bcrypt('123456'),
                ]);
                $parent->assignRole('parent');
            } else {
                // Update parent name if changed
                if ($parentName && $parent->name !== $parentName) {
                    $parent->update(['name' => $parentName]);
                }
            }

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
