<?php

namespace App\Filament\Resources\VoucherCampaigns\Pages;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Vouchers\Actions\AddWinner;
use App\Domain\Vouchers\Actions\RecordShortfall;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Filament\Resources\VoucherCampaigns\VoucherCampaignResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewVoucherCampaign extends ViewRecord
{
    protected static string $resource = VoucherCampaignResource::class;

    public function getTitle(): string
    {
        /** @var VoucherCampaign $campaign */
        $campaign = $this->getRecord();

        return 'Cadeaubonnenactie '.$campaign->company->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addWinner')
                ->label('Winnaar aanvullen')
                ->icon(Heroicon::OutlinedUserPlus)
                ->modalDescription('Bonbeheer vult aan bij een tekort; de winnaar ontvangt direct de claimlink.')
                ->visible(fn () => $this->isVoucherManager() && in_array($this->getRecord()->status, [CampaignStatus::Open, CampaignStatus::Closed], true))
                ->schema([
                    TextInput::make('first_name')->label('Voornaam')->required()->maxLength(60),
                    TextInput::make('last_initial')->label('Eerste letter achternaam')->required()->maxLength(4),
                    TextInput::make('email')->label('E-mailadres')->email()->required()->maxLength(190),
                ])
                ->action(function (array $data, AddWinner $add): void {
                    $this->run(fn () => $add($this->getRecord(), $data['first_name'], $data['last_initial'], $data['email'], null, asVoucherManager: true), 'Winnaar toegevoegd; claimlink verstuurd');
                }),
            Action::make('shortfall')
                ->label('Tekort vastleggen')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning')
                ->visible(fn () => $this->isVoucherManager() && $this->getRecord()->status !== CampaignStatus::Draft)
                ->schema([
                    TextInput::make('count')->label('Aantal bonnen tekort')->numeric()->minValue(1)->required()
                        ->default(fn () => max(1, $this->getRecord()->remainingSlots())),
                    Textarea::make('note')->label('Toelichting')->rows(2),
                ])
                ->action(function (array $data, RecordShortfall $record): void {
                    $this->run(fn () => $record($this->getRecord(), (int) $data['count'], $data['note'] ?? null, auth()->user()), 'Tekort vastgelegd; door te factureren via Financiën');
                }),
            Action::make('markInvoiced')
                ->label('Tekort gefactureerd')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn () => (auth()->user()?->hasAnyRole([StaffRole::Finance->value, StaffRole::VoucherManager->value, StaffRole::Admin->value]) ?? false) && $this->getRecord()->shortfalls()->whereNull('invoiced_at')->exists())
                ->action(function (): void {
                    $this->getRecord()->shortfalls()->whereNull('invoiced_at')->update(['invoiced_at' => now()]);
                    Notification::make()->title('Tekort als gefactureerd gemarkeerd')->success()->send();
                }),
        ];
    }

    private function isVoucherManager(): bool
    {
        return auth()->user()?->hasAnyRole([StaffRole::VoucherManager->value, StaffRole::Admin->value]) ?? false;
    }

    private function run(callable $callback, string $successTitle): void
    {
        try {
            $callback();
            Notification::make()->title($successTitle)->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
        }
    }
}
