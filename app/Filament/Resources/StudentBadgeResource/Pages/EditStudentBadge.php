<?php

namespace App\Filament\Resources\StudentBadgeResource\Pages;

use App\Filament\Resources\StudentBadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentBadge extends EditRecord
{
    protected static string $resource = StudentBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
