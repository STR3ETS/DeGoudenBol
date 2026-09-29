<?php

namespace App\Filament\Resources\Charities\Pages;

use App\Domain\Charities\Actions\NominateCharity;
use App\Domain\Charities\Exceptions\CharityException;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Edition\Models\Edition;
use App\Filament\Resources\Charities\CharityResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateCharity extends CreateRecord
{
    protected static string $resource = CharityResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $sponsor = Sponsor::query()->findOrFail($data['nominated_by_id']);
        $edition = Edition::current();

        if ($edition === null) {
            Notification::make()->title('Geen lopende editie')->danger()->send();

            throw new Halt;
        }

        try {
            return app(NominateCharity::class)($sponsor, $edition, $data);
        } catch (CharityException $exception) {
            Notification::make()->title('Niet voorgedragen')->body($exception->getMessage())->danger()->send();

            throw new Halt;
        }
    }
}
