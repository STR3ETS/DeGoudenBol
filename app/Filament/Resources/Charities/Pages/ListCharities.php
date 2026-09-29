<?php

namespace App\Filament\Resources\Charities\Pages;

use App\Filament\Resources\Charities\CharityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCharities extends ListRecords
{
    protected static string $resource = CharityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Voordracht namens sponsor'),
        ];
    }
}
