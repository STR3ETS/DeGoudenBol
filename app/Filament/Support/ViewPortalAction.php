<?php

namespace App\Filament\Support;

use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\PortalViewing;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * De knop "Portaal bekijken" bij een bedrijf, inschrijving of deelnemersaccount.
 * Opent het portaal zoals de bakker het ziet: alleen lezen en vastgelegd in de auditlog.
 */
final class ViewPortalAction
{
    public static function make(): Action
    {
        return Action::make('viewPortal')
            ->label('Portaal bekijken')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (): bool => PortalViewing::allows(auth()->user()))
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedEye)
            ->modalHeading('Portaal bekijken')
            ->modalDescription('Je opent het portaal zoals de bakker het ziet. Je kunt alleen kijken, niet wijzigen; rapport en uitslag blijven dicht. Deze inzage komt op jouw naam in het logboek en stopt vanzelf na '.PortalViewing::MINUTES.' minuten.')
            ->modalSubmitActionLabel('Portaal openen')
            ->action(function (Model $record, Component $livewire, PortalViewing $viewing): void {
                $company = match (true) {
                    $record instanceof Company => $record,
                    $record instanceof Entry => $record->company,
                    $record instanceof ParticipantUser => $record->companies()->active()->orderBy('companies.name')->first(),
                    default => null,
                };

                $account = $record instanceof ParticipantUser ? $record : ($company ? $viewing->accountFor($company) : null);

                if ($company === null || $account === null) {
                    Notification::make()
                        ->title('Geen portaal om te openen')
                        ->body('Bij dit bedrijf hoort nog geen account waarmee iemand in het portaal kan inloggen.')
                        ->warning()
                        ->send();

                    return;
                }

                $viewing->start(auth()->user(), $company, $account);

                $livewire->redirect(route('portaal.dashboard'));
            });
    }
}
