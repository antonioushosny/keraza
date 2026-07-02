<?php

namespace App\Filament\Resources\ActivityScoringRuleResource\Pages;

use App\Filament\Resources\ActivityScoringRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityScoringRule extends EditRecord
{
    protected static string $resource = ActivityScoringRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
