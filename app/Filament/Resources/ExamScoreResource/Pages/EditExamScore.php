<?php

namespace App\Filament\Resources\ExamScoreResource\Pages;

use App\Filament\Resources\ExamScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamScore extends EditRecord
{
    protected static string $resource = ExamScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
