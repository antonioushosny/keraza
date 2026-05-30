<?php

namespace App\Filament\Resources\KerazaClassResource\Pages;

use App\Filament\Resources\KerazaClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKerazaClass extends EditRecord
{
    protected static string $resource = KerazaClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
