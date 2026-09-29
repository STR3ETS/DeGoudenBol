<?php

namespace App\Filament\Resources\MediaContacts\Pages;

use App\Filament\Resources\MediaContacts\MediaContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMediaContacts extends ManageRecords
{
    protected static string $resource = MediaContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Contact toevoegen'),
        ];
    }
}
