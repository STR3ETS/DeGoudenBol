<?php

namespace App\Filament\Resources\DeliverySlots\Pages;

use App\Filament\Resources\DeliverySlots\DeliverySlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDeliverySlots extends ManageRecords
{
    protected static string $resource = DeliverySlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Slot toevoegen'),
        ];
    }
}
