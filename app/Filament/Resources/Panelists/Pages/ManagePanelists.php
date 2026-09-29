<?php

namespace App\Filament\Resources\Panelists\Pages;

use App\Filament\Resources\Panelists\PanelistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePanelists extends ManageRecords
{
    protected static string $resource = PanelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Panellid toevoegen'),
        ];
    }
}
