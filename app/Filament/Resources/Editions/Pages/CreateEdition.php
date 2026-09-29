<?php

namespace App\Filament\Resources\Editions\Pages;

use App\Domain\Edition\Settings\EditionSettings;
use App\Filament\Resources\Editions\EditionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEdition extends CreateRecord
{
    protected static string $resource = EditionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['settings'] = (new EditionSettings)->with($data['settings'] ?? [])->toArray();

        return $data;
    }
}
