<?php

namespace App\Filament\Resources\TestLocations\Pages;

use App\Filament\Resources\TestLocations\TestLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTestLocations extends ManageRecords
{
    protected static string $resource = TestLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
