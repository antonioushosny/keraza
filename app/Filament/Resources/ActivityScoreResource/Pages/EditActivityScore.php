<?php

namespace App\Filament\Resources\ActivityScoreResource\Pages;

use App\Filament\Resources\ActivityScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityScore extends EditRecord
{
    protected static string $resource = ActivityScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
