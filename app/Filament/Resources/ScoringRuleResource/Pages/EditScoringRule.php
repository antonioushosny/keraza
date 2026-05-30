<?php

namespace App\Filament\Resources\ScoringRuleResource\Pages;

use App\Filament\Resources\ScoringRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScoringRule extends EditRecord
{
    protected static string $resource = ScoringRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
