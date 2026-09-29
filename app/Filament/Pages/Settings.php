<?php

namespace App\Filament\Pages;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Edition\Models\TestLocation;
use App\Filament\Resources\Editions\EditionResource;
use App\Filament\Resources\Provinces\ProvinceResource;
use App\Filament\Resources\ScoringModels\ScoringModelResource;
use App\Filament\Resources\TermsVersions\TermsVersionResource;
use App\Filament\Resources\TestLocations\TestLocationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Instellingen: alles wat per editie kan verschillen, bereikbaar via de voet van de sidebar.
 * De onderliggende resources staan niet in het hoofdmenu (docs/02 §7).
 */
class Settings extends Page
{
    protected string $view = 'filament.pages.instellingen';

    protected static ?string $slug = 'instellingen';

    protected static ?string $title = 'Instellingen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static bool $shouldRegisterNavigation = false;

    public function getSubheading(): ?string
    {
        return 'Alles wat per editie kan verschillen staat hier, niet in de code.';
    }

    /**
     * @return list<array{title: string, text: string, meta: string, url: string, icon: string}>
     */
    public function cards(): array
    {
        $edition = Edition::current();
        $cards = [];

        if (EditionResource::canViewAny()) {
            $cards[] = [
                'title' => $edition === null ? 'Editie' : "Editie {$edition->year}",
                'text' => 'Datums, capaciteit per provincie, deelnamemodel, versheidsvenster en publicatiemomenten.',
                'meta' => $edition === null ? 'Nog geen editie' : $edition->status->getLabel(),
                'url' => $edition === null ? EditionResource::getUrl('index') : EditionResource::getUrl('edit', ['record' => $edition]),
                'icon' => 'heroicon-o-calendar-days',
            ];
        }

        if (ProvinceResource::canViewAny()) {
            $cards[] = [
                'title' => 'Provincies',
                'text' => 'De twaalf provincies met naam, code en de koppeling aan de editie.',
                'meta' => Province::query()->count().' provincies',
                'url' => ProvinceResource::getUrl('index'),
                'icon' => 'heroicon-o-map',
            ];
        }

        if (ScoringModelResource::canViewAny()) {
            $cards[] = [
                'title' => 'Beoordelingsmodel',
                'text' => 'Acht onderdelen, honderd punten en de versie die het panel gebruikt.',
                'meta' => ScoringModel::query()->count().' modellen',
                'url' => ScoringModelResource::getUrl('index'),
                'icon' => 'heroicon-o-scale',
            ];
        }

        if (TermsVersionResource::canViewAny()) {
            $cards[] = [
                'title' => 'Voorwaarden en privacy',
                'text' => 'Versies van de deelnamevoorwaarden en de privacyverklaring waar bakkers mee akkoord gaan.',
                'meta' => TermsVersion::query()->count().' versies',
                'url' => TermsVersionResource::getUrl('index'),
                'icon' => 'heroicon-o-document-text',
            ];
        }

        if (TestLocationResource::canViewAny()) {
            $cards[] = [
                'title' => 'Testlocaties',
                'text' => 'Waar monsters worden ontvangen, genummerd en geproefd.',
                'meta' => TestLocation::query()->count().' locaties',
                'url' => TestLocationResource::getUrl('index'),
                'icon' => 'heroicon-o-map-pin',
            ];
        }

        if (UserResource::canViewAny()) {
            $cards[] = [
                'title' => 'Medewerkers',
                'text' => 'Accounts, rollen, functiescheiding en tweestapsverificatie.',
                'meta' => User::query()->where('is_active', true)->count().' actief',
                'url' => UserResource::getUrl('index'),
                'icon' => 'heroicon-o-users',
            ];
        }

        return $cards;
    }
}
