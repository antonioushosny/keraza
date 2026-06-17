<?php

namespace App\Filament\Resources\ActivityAttendanceSessionResource\Pages;

use App\Filament\Resources\ActivityAttendanceSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityAttendanceSessions extends ListRecords
{
    protected static string $resource = ActivityAttendanceSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
