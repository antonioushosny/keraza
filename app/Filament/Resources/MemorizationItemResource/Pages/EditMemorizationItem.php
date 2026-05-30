<?php

namespace App\Filament\Resources\MemorizationItemResource\Pages;

use App\Filament\Resources\MemorizationItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMemorizationItem extends EditRecord
{
    protected static string $resource = MemorizationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
