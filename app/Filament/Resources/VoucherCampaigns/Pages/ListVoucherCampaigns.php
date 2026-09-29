<?php

namespace App\Filament\Resources\VoucherCampaigns\Pages;

use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Vouchers\Actions\CreateVoucherCampaigns;
use App\Filament\Resources\VoucherCampaigns\VoucherCampaignResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListVoucherCampaigns extends ListRecords
{
    protected static string $resource = VoucherCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createForEdition')
                ->label('Acties aanmaken voor de Top 10')
                ->icon(Heroicon::OutlinedSparkles)
                ->requiresConfirmation()
                ->modalDescription('Maakt voor iedere ondernemer in een bevroren provinciale Top 10 een cadeaubonnenactie aan (bestaande acties blijven staan). Gebeurt normaal automatisch bij de bevriezing.')
                ->visible(fn () => auth()->user()?->hasAnyRole([StaffRole::VoucherManager->value, StaffRole::Admin->value]) ?? false)
                ->action(function (CreateVoucherCampaigns $create): void {
                    $edition = Edition::current();

                    if ($edition === null) {
                        Notification::make()->title('Geen lopende editie')->warning()->send();

                        return;
                    }

                    $count = $create($edition);

                    Notification::make()->title("{$count} nieuwe acties aangemaakt")->success()->send();
                }),
        ];
    }
}
