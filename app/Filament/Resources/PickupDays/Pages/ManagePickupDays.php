<?php

namespace App\Filament\Resources\PickupDays\Pages;

use App\Filament\Resources\PickupDays\PickupDayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePickupDays extends ManageRecords
{
    protected static string $resource = PickupDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Vrijdag toevoegen'),
        ];
    }
}
