<?php

namespace App\Filament\Resources\ActivityScoreResource\Pages;

use App\Filament\Resources\ActivityScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityScores extends ListRecords
{
    protected static string $resource = ActivityScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
