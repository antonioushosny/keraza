<?php

namespace App\Filament\Resources\ExamScoreResource\Pages;

use App\Filament\Resources\ExamScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamScores extends ListRecords
{
    protected static string $resource = ExamScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
