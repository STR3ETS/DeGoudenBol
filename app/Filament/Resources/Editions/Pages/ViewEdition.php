<?php

namespace App\Filament\Resources\Editions\Pages;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\FreezeEdition;
use App\Domain\Ranking\Actions\RevealProvinces;
use App\Filament\Resources\Editions\EditionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewEdition extends ViewRecord
{
    protected static string $resource = EditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('freeze')
                ->label('Voorlijsten bevriezen')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Voorlijsten bevriezen')
                ->modalDescription('In één transactie: definitieve Top 10 per provincie, provinciewinnaars en finale-inschrijvingen. De lijsten blijven onder embargo tot de reveal per provincie op de publicatiedag. Open beslisrondes blokkeren de bevriezing.')
                ->visible(fn () => $this->isPublisher() && in_array($this->getRecord()->status, [EditionStatus::Testing, EditionStatus::Closed], true))
                ->action(fn (FreezeEdition $freeze) => $this->run(fn () => $freeze($this->getRecord(), auth()->user()), 'Voorlijsten bevroren')),
            Action::make('reveal')
                ->label('Provincies nu onthullen')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Normaal onthult het systeem iedere provincie op het ingestelde reveal-moment. Deze knop onthult nu alle provincies waarvan het moment al verstreken is.')
                ->visible(fn () => $this->isPublisher() && $this->getRecord()->status === EditionStatus::Frozen)
                ->action(fn (RevealProvinces $reveal) => $this->run(function () use ($reveal): string {
                    /** @var Edition $edition */
                    $edition = $this->getRecord();
                    $names = $reveal($edition);

                    return $names === [] ? 'Geen provincie waarvan het reveal-moment al verstreken is.' : 'Onthuld: '.implode(', ', $names);
                })),
            EditAction::make(),
        ];
    }

    private function isPublisher(): bool
    {
        return auth()->user()?->hasAnyRole([StaffRole::Publisher->value, StaffRole::Admin->value]) ?? false;
    }

    private function run(callable $callback, ?string $successTitle = null): void
    {
        try {
            $message = $callback();
            Notification::make()->title($successTitle ?? (is_string($message) ? $message : 'Verwerkt'))->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Niet uitgevoerd')->body($exception->getMessage())->danger()->send();
        }
    }
}
