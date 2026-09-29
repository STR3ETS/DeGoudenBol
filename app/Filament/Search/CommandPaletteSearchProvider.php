<?php

namespace App\Filament\Search;

use App\Filament\Pages\Settings;
use App\Filament\Resources\Editions\EditionResource;
use App\Filament\Resources\Provinces\ProvinceResource;
use App\Filament\Resources\ScoringModels\ScoringModelResource;
use App\Filament\Resources\TermsVersions\TermsVersionResource;
use App\Filament\Resources\TestLocations\TestLocationResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

/**
 * Zoeken als commandopalet: eerst pagina's en acties ("nieuwe testsessie"), daarna de
 * records uit de resources (bakkers, testnummers, ordernummers, KvK).
 */
final class CommandPaletteSearchProvider implements GlobalSearchProvider
{
    /**
     * Woorden waarmee medewerkers een pagina zoeken zonder de menunaam te kennen.
     *
     * @var array<string, list<string>>
     */
    private const array SYNONYMS = [
        'Bedrijven' => ['bakker', 'kvk', 'kraam', 'adres'],
        'Inschrijvingen' => ['aanmelding', 'deelnemer', 'status'],
        'Accounts' => ['login', 'inloggen', 'wachtwoord', 'inloglink'],
        'Profielteksten keuren' => ['moderatie', 'verhaal', 'tekst'],
        'Ontvangst & registratie' => ['ontvangst', 'scannen', 'qr', 'etiket', 'testnummer', 'inname'],
        'Aanleverslots' => ['slot', 'tijdslot', 'levering', 'aanleveren'],
        'Testsessies' => ['sessie', 'uitserveren', 'schema', 'testdag'],
        'Panelleden' => ['panel', 'jury', 'allergenen'],
        'Scorecontrole' => ['monster', 'score', 'afwijker', 'kaarten', 'cijfer'],
        'Publicaties' => ['batch', 'publiceren', 'goedkeuren', 'uitslag', 'voorlijst'],
        'Terugkoppeling onder 5,0' => ['vertrouwelijk', 'rapport'],
        'Bezwaren' => ['bezwaar', 'klacht'],
        'Beslisrondes' => ['gelijke stand', 'beslissing'],
        'Finale' => ['finalist', 'top 10', 'uitnodiging'],
        'Correcties na publicatie' => ['correctie', 'dossier'],
        'Nieuws' => ['bericht', 'artikel'],
        'Badges' => ['erkenning', 'embleem', 'socialkit'],
        'Cadeaubonnen' => ['bon', 'bonnen', 'voucher', 'winnaar', 'verzilveren'],
        'Persberichten' => ['pers', 'embargo', 'perskit'],
        'Mediacontacten' => ['journalist', 'redactie', 'media'],
        'Nieuwsbrief' => ['opt-in', 'abonnee', 'inschrijving nieuwsbrief'],
        'Orders' => ['betaling', 'ideal', 'bankoverschrijving', 'order'],
        'Facturen' => ['factuur', 'btw', 'vervaldatum'],
        'Pakketten en prijzen' => ['pakket', 'prijs', 'tarief', 'deelname'],
        'Sponsoren' => ['sponsor', 'logo'],
        'Sponsorproducten' => ['catalogus', 'plaatsing', 'advertentie'],
        'Goede doelen' => ['charity', 'reservering', 'uitbetaling'],
    ];

    public function getResults(string $query): ?GlobalSearchResults
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        $needles = array_values(array_filter(preg_split('/\s+/', mb_strtolower($query)) ?: []));
        $results = GlobalSearchResults::make();

        $pages = $this->pages($needles);

        if ($pages !== []) {
            $results->category('Pagina\'s', $pages);
        }

        $actions = $this->actions($needles);

        if ($actions !== []) {
            $results->category('Acties', $actions);
        }

        $records = (new DefaultGlobalSearchProvider)->getResults($query);

        if ($records !== null) {
            foreach ($records->getCategories() as $name => $categoryResults) {
                $results->category($name, $categoryResults);
            }
        }

        return $results;
    }

    /**
     * @param  list<string>  $needles
     * @return list<GlobalSearchResult>
     */
    private function pages(array $needles): array
    {
        $pages = [];

        /** @var NavigationGroup $group */
        foreach (Filament::getNavigation() as $group) {
            $groupLabel = (string) ($group->getLabel() ?? '');

            /** @var NavigationItem $item */
            foreach ($group->getItems() as $item) {
                $label = (string) $item->getLabel();
                $url = $item->getUrl();

                if (blank($url)) {
                    continue;
                }

                if ($this->matches($needles, $label.' '.$groupLabel.' '.implode(' ', self::SYNONYMS[$label] ?? []))) {
                    $pages[] = new GlobalSearchResult(title: $label, url: $url, details: [$groupLabel !== '' ? $groupLabel : 'Pagina']);
                }
            }
        }

        foreach ($this->settingsPages() as [$label, $url, $keywords]) {
            if ($url !== null && $this->matches($needles, $label.' instellingen '.$keywords)) {
                $pages[] = new GlobalSearchResult(title: $label, url: $url, details: ['Instellingen']);
            }
        }

        return array_slice($pages, 0, 8);
    }

    /**
     * Pagina's die niet in het menu staan maar wel vindbaar moeten zijn.
     *
     * @return list<array{0: string, 1: string|null, 2: string}>
     */
    private function settingsPages(): array
    {
        return [
            ['Instellingen', Settings::getUrl(), 'editie configuratie beheer'],
            ['Editie', EditionResource::canViewAny() ? EditionResource::getUrl('index') : null, 'edities datums capaciteit jaar seizoen'],
            ['Provincies', ProvinceResource::canViewAny() ? ProvinceResource::getUrl('index') : null, 'provincie capaciteit reveal'],
            ['Beoordelingsmodel', ScoringModelResource::canViewAny() ? ScoringModelResource::getUrl('index') : null, 'onderdelen punten criteria model'],
            ['Voorwaarden en privacy', TermsVersionResource::canViewAny() ? TermsVersionResource::getUrl('index') : null, 'voorwaarden privacy juridisch versie'],
            ['Testlocaties', TestLocationResource::canViewAny() ? TestLocationResource::getUrl('index') : null, 'locatie adres testruimte'],
            ['Medewerkers', UserResource::canViewAny() ? UserResource::getUrl('index') : null, 'gebruikers rollen accounts tweestapsverificatie collega'],
        ];
    }

    /**
     * "Nieuwe testsessie", "nieuwe editie": de aanmaakpagina's van resources die er een hebben.
     *
     * @param  list<string>  $needles
     * @return list<GlobalSearchResult>
     */
    private function actions(array $needles): array
    {
        $actions = [];

        foreach (Filament::getResources() as $resource) {
            if (! $resource::hasPage('create') || ! $resource::canCreate()) {
                continue;
            }

            $label = 'Nieuwe '.$resource::getModelLabel();

            if ($this->matches($needles, $label.' nieuw aanmaken toevoegen '.$resource::getPluralModelLabel())) {
                $actions[] = new GlobalSearchResult(title: $label, url: $resource::getUrl('create'), details: ['Aanmaken']);
            }
        }

        return array_slice($actions, 0, 5);
    }

    /**
     * Alle zoekwoorden moeten ergens in de tekst voorkomen.
     *
     * @param  list<string>  $needles
     */
    private function matches(array $needles, string $haystack): bool
    {
        $haystack = mb_strtolower($haystack);

        foreach ($needles as $needle) {
            if (! str_contains($haystack, $needle)) {
                return false;
            }
        }

        return $needles !== [];
    }
}
