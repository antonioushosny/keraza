<?php

namespace App\Filament\Resources\MemorizationItemResource\Pages;

use App\Filament\Resources\MemorizationItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMemorizationItems extends ListRecords
{
    protected static string $resource = MemorizationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
