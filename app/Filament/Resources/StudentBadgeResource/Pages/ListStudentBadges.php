<?php

namespace App\Filament\Resources\StudentBadgeResource\Pages;

use App\Filament\Resources\StudentBadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentBadges extends ListRecords
{
    protected static string $resource = StudentBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
