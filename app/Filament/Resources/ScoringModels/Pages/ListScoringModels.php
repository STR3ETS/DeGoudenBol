<?php

namespace App\Filament\Resources\ScoringModels\Pages;

use App\Filament\Resources\ScoringModels\ScoringModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScoringModels extends ListRecords
{
    protected static string $resource = ScoringModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
