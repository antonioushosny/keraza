<?php

namespace App\Filament\Resources\ActivityTaskResource\Pages;

use App\Filament\Resources\ActivityTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityTasks extends ListRecords
{
    protected static string $resource = ActivityTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
