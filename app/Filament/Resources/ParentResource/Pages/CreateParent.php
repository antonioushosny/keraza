<?php

namespace App\Filament\Resources\ParentResource\Pages;

use App\Filament\Resources\ParentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateParent extends CreateRecord
{
    protected static string $resource = ParentResource::class;

    public array $studentIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->studentIds = $data['student_ids'] ?? [];
        unset($data['student_ids']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole('parent');

        if (!empty($this->studentIds)) {
            \App\Models\Student::whereIn('id', $this->studentIds)->update(['parent_id' => $this->record->id]);
        }
    }
}
