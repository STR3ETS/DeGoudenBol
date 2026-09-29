<?php

namespace App\Filament\Resources\Sponsors\Pages;

use App\Domain\Commerce\Actions\CreateSponsorOrder;
use App\Domain\Commerce\Enums\PlacementStatus;
use App\Domain\Commerce\Exceptions\SponsoringException;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Resources\Sponsors\SponsorResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSponsor extends EditRecord
{
    protected static string $resource = SponsorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invoice')
                ->label('Factureren')
                ->icon(Heroicon::OutlinedDocumentCurrencyEuro)
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Maakt één order voor alle gereserveerde plaatsingen die nog niet gefactureerd zijn. Na betaling (Mollie of handmatig bij Orders) worden de plaatsingen actief en wordt 10% gereserveerd voor het goede doel.')
                ->visible(fn () => (auth()->user()?->hasAnyRole([StaffRole::Finance->value, StaffRole::Admin->value]) ?? false) && $this->sponsor()->placements()->where('status', PlacementStatus::Draft)->whereNull('order_line_id')->exists())
                ->action(function (CreateSponsorOrder $create): void {
                    try {
                        $edition = Edition::current();
                        $order = $create($this->sponsor(), $edition);
                        Notification::make()->title("Order {$order->number} aangemaakt ({$order->formattedTotal()} incl. btw)")->success()->send();
                    } catch (SponsoringException $exception) {
                        Notification::make()->title('Niet gefactureerd')->body($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    private function sponsor(): Sponsor
    {
        /** @var Sponsor $sponsor */
        $sponsor = $this->getRecord();

        return $sponsor;
    }
}
