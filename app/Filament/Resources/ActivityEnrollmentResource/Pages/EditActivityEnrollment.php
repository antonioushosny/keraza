<?php

namespace App\Filament\Resources\ActivityEnrollmentResource\Pages;

use App\Filament\Resources\ActivityEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityEnrollment extends EditRecord
{
    protected static string $resource = ActivityEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
