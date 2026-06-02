<?php

namespace App\Filament\Resources\ParentResource\Pages;

use App\Filament\Resources\ParentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParent extends EditRecord
{
    protected static string $resource = ParentResource::class;

    public array $studentIds = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->studentIds = $data['student_ids'] ?? [];
        unset($data['student_ids']);
        return $data;
    }

    protected function afterSave(): void
    {
        // Dissociate students that are no longer selected
        \App\Models\Student::where('parent_id', $this->record->id)
            ->whereNotIn('id', $this->studentIds)
            ->update(['parent_id' => null]);

        // Associate selected students
        if (!empty($this->studentIds)) {
            \App\Models\Student::whereIn('id', $this->studentIds)->update(['parent_id' => $this->record->id]);
        }
    }
}
