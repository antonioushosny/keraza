<?php

namespace App\Filament\Resources\MemorizationScoreResource\Pages;

use App\Filament\Resources\MemorizationScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMemorizationScore extends EditRecord
{
    protected static string $resource = MemorizationScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
