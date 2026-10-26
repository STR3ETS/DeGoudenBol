<?php

namespace App\Filament\Pages;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TermsVersion;
use App\Filament\Resources\Editions\EditionResource;
use App\Filament\Support\SetupChecklist;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Inrichten: de checklist om het seizoen te starten, met één knop om de inschrijving open te zetten.
 */
class Setup extends Page
{
    protected string $view = 'filament.pages.inrichten';

    protected static ?string $slug = 'inrichten';

    protected static ?string $title = 'Inrichten';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return EditionResource::canViewAny();
    }

    public function getHeading(): string
    {
        return '';
    }

    public function checklist(): SetupChecklist
    {
        return new SetupChecklist(Edition::current());
    }

    public function openRegistrationAction(): Action
    {
        return Action::make('openRegistration')
            ->label('Inschrijving nu openzetten')
            ->requiresConfirmation()
            ->modalHeading('Inschrijving nu openzetten?')
            ->modalDescription('De aanmeldpagina gaat direct aan. Bakkers kunnen zich dan aanmelden en betalen.')
            ->modalSubmitActionLabel('Ja, openzetten')
            ->action(function (): void {
                $edition = Edition::current();
                $checklist = new SetupChecklist($edition);

                if ($edition === null || ! $checklist->registration()['can_open']) {
                    Notification::make()->title('De inschrijving kan nog niet open')->body(implode('; ', $checklist->blockers()) ?: 'Maak eerst de punten op deze pagina af.')->danger()->send();

                    return;
                }

                $edition->forceFill([
                    'status' => EditionStatus::RegistrationOpen,
                    'registration_opens_at' => now(),
                    'registration_closes_at' => $edition->registrationHasClosed() ? null : $edition->registration_closes_at,
                ])->save();

                Notification::make()->title('De inschrijving is open')->body('Bakkers kunnen zich nu aanmelden op '.route('aanmelden').'.')->success()->send();
            });
    }

    public function closeRegistrationAction(): Action
    {
        return Action::make('closeRegistration')
            ->label('Inschrijving sluiten')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Inschrijving sluiten?')
            ->modalDescription('De aanmeldpagina gaat direct dicht. Bakkers die al betaald hebben, blijven gewoon meedoen. Je kunt hier later weer openzetten.')
            ->modalSubmitActionLabel('Ja, sluiten')
            ->action(function (): void {
                $edition = Edition::current();

                if ($edition === null) {
                    return;
                }

                $edition->forceFill(['registration_closes_at' => now()])->save();

                Notification::make()->title('De inschrijving is gesloten')->success()->send();
            });
    }

    public function publishTermsAction(): Action
    {
        return Action::make('publishTerms')
            ->label('Concept publiceren')
            ->requiresConfirmation()
            ->modalHeading('Concepten publiceren?')
            ->modalDescription('De nieuwste conceptversie van de deelnamevoorwaarden en de privacyverklaring wordt gepubliceerd. Bakkers gaan vanaf nu met deze tekst akkoord.')
            ->modalSubmitActionLabel('Ja, publiceren')
            ->action(function (): void {
                $published = 0;

                foreach ([TermsType::Participation, TermsType::Privacy] as $type) {
                    if (TermsVersion::latestPublished($type) !== null) {
                        continue;
                    }

                    $draft = TermsVersion::query()->where('type', $type)->whereNull('published_at')->orderByDesc('id')->first();

                    if ($draft !== null) {
                        $draft->forceFill(['published_at' => now()])->save();
                        $published++;
                    }
                }

                if ($published === 0) {
                    Notification::make()->title('Geen concept gevonden')->body('Maak eerst een versie aan bij Voorwaarden en privacy.')->warning()->send();

                    return;
                }

                Notification::make()->title($published === 1 ? 'Concept gepubliceerd' : 'Concepten gepubliceerd')->success()->send();
            });
    }
}
