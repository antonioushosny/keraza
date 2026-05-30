<?php

namespace App\Filament\Resources\KerazaClassResource\Pages;

use App\Filament\Resources\KerazaClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKerazaClasses extends ListRecords
{
    protected static string $resource = KerazaClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
