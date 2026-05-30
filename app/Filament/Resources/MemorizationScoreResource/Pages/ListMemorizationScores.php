<?php

namespace App\Filament\Resources\MemorizationScoreResource\Pages;

use App\Filament\Resources\MemorizationScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMemorizationScores extends ListRecords
{
    protected static string $resource = MemorizationScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
