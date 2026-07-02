<?php

namespace App\Filament\Resources\ActivityScoringRuleResource\Pages;

use App\Filament\Resources\ActivityScoringRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityScoringRules extends ListRecords
{
    protected static string $resource = ActivityScoringRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
