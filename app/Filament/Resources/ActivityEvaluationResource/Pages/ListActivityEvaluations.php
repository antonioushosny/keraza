<?php

namespace App\Filament\Resources\ActivityEvaluationResource\Pages;

use App\Filament\Resources\ActivityEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityEvaluations extends ListRecords
{
    protected static string $resource = ActivityEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
