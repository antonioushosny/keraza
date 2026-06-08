<?php

namespace App\Filament\Resources\AttendanceSessionResource\Pages;

use App\Services\AttendanceSyncService;
use App\Filament\Resources\AttendanceSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceSession extends EditRecord
{
    protected static string $resource = AttendanceSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        (new \App\Services\AttendanceSyncService())->syncSessionAttendances($this->record);
        return $data;
    }
}

