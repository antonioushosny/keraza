<?php

namespace App\Filament\Resources\ActivityEnrollmentResource\Pages;

use App\Filament\Resources\ActivityEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityEnrollments extends ListRecords
{
    protected static string $resource = ActivityEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
