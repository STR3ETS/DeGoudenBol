<?php

namespace App\Filament\Resources\Editions\Pages;

use App\Domain\Edition\Models\Edition;
use App\Filament\Resources\Editions\EditionResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEdition extends EditRecord
{
    protected static string $resource = EditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * Instellingen die niet in het formulier staan blijven behouden.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Edition $edition */
        $edition = $this->getRecord();

        $data['settings'] = $edition->settings->with($data['settings'] ?? [])->toArray();

        return $data;
    }
}
