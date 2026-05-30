<?php

namespace App\Filament\Resources\StudentSeasonEnrollmentResource\Pages;

use App\Filament\Resources\StudentSeasonEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentSeasonEnrollment extends EditRecord
{
    protected static string $resource = StudentSeasonEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
