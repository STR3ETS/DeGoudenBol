<?php

namespace App\Filament\Support;

use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Edition\Models\TestLocation;
use App\Filament\Resources\Editions\EditionResource;
use App\Filament\Resources\Packages\PackageResource;
use App\Filament\Resources\ScoringModels\ScoringModelResource;
use App\Filament\Resources\TermsVersions\TermsVersionResource;
use App\Filament\Resources\TestLocations\TestLocationResource;
use App\Support\DutchTime;

/**
 * "Inrichten": wat er klaar moet staan voordat de inschrijving open kan, in gewone taal,
 * en de knop om de inschrijving daadwerkelijk open of dicht te zetten.
 *
 * @phpstan-type Item array{key: string, title: string, text: string, done: bool, optional: bool, url: string|null, action: string|null, icon: string}
 * @phpstan-type Registration array{state: 'open'|'blocked'|'closed'|'none', headline: string, text: string, reasons: list<string>, can_open: bool, can_close: bool, opens_at: string|null, closes_at: string|null, url: string}
 */
final class SetupChecklist
{
    public function __construct(private readonly ?Edition $edition) {}

    /**
     * @return list<Item>
     */
    public function items(): array
    {
        $edition = $this->edition;
        $items = [];

        $items[] = [
            'key' => 'editie',
            'title' => $edition === null ? 'Maak de editie aan' : "Editie {$edition->year} bestaat",
            'text' => $edition === null ? 'Alles hangt aan een editie: datums, plekken, prijzen en voorwaarden. Maak eerst de editie van dit jaar aan.' : 'Status: '.$edition->status->getLabel().'. Datums en plekken stel je hieronder in.',
            'done' => $edition !== null,
            'optional' => false,
            'url' => EditionResource::canViewAny() ? ($edition === null ? EditionResource::getUrl('create') : EditionResource::getUrl('edit', ['record' => $edition])) : null,
            'action' => $edition === null ? 'Editie aanmaken' : 'Bekijken',
            'icon' => 'heroicon-o-calendar-days',
        ];

        $missingDates = [];

        if ($edition !== null) {
            foreach ([['first_test_day', 'eerste testdag'], ['last_test_day', 'laatste testdag'], ['main_publication_at', 'hoofdpublicatie']] as [$field, $label]) {
                if ($edition->{$field} === null) {
                    $missingDates[] = $label;
                }
            }
        }

        $items[] = [
            'key' => 'datums',
            'title' => 'Belangrijke datums',
            'text' => $edition === null ? 'Eerst de editie.' : ($missingDates === [] ? 'Testdagen '.DutchTime::date($edition->first_test_day).' t/m '.DutchTime::date($edition->last_test_day).', hoofdpublicatie '.DutchTime::format($edition->main_publication_at, 'D MMMM [om] HH:mm').'.' : 'Nog niet ingevuld: '.implode(', ', $missingDates).'. Bakkers zien deze datums op de site en in het portaal.'),
            'done' => $edition !== null && $missingDates === [],
            'optional' => false,
            'url' => $edition !== null && EditionResource::canViewAny() ? EditionResource::getUrl('edit', ['record' => $edition]) : null,
            'action' => 'Datums instellen',
            'icon' => 'heroicon-o-clock',
        ];

        $provinces = $edition?->provinces ?? collect();
        $capacity = (int) $provinces->sum(fn ($province) => (int) $province->pivot->capacity);

        $items[] = [
            'key' => 'provincies',
            'title' => 'Plekken per provincie',
            'text' => $edition === null ? 'Eerst de editie.' : ($capacity > 0 ? $provinces->count().' provincies, samen '.$capacity.' plekken. Vol is vol: daarna komt een bakker op de wachtlijst.' : 'Koppel de provincies aan de editie en geef elke provincie een aantal plekken. Zonder plekken kan niemand zich aanmelden.'),
            'done' => $capacity > 0,
            'optional' => false,
            'url' => $edition !== null && EditionResource::canViewAny() ? EditionResource::getUrl('edit', ['record' => $edition]) : null,
            'action' => 'Plekken instellen',
            'icon' => 'heroicon-o-map',
        ];

        $packages = $edition === null ? collect() : Package::query()->where('edition_id', $edition->getKey())->active()->get();

        $items[] = [
            'key' => 'pakketten',
            'title' => 'Deelnameprijs',
            'text' => $edition === null ? 'Eerst de editie.' : ($packages->isNotEmpty() ? $packages->map(fn (Package $package) => $package->name.' '.$package->formattedPriceInclVat())->implode(', ').'.' : 'Maak minstens één actief pakket met de prijs voor deelname. Dat is wat de bakker afrekent bij het aanmelden.'),
            'done' => $packages->isNotEmpty(),
            'optional' => false,
            'url' => PackageResource::canViewAny() ? PackageResource::getUrl('index') : null,
            'action' => 'Pakketten instellen',
            'icon' => 'heroicon-o-tag',
        ];

        $terms = TermsVersion::latestPublished(TermsType::Participation);
        $privacy = TermsVersion::latestPublished(TermsType::Privacy);
        $drafts = TermsVersion::query()->whereNull('published_at')->whereIn('type', [TermsType::Participation, TermsType::Privacy])->get();
        $missingTerms = array_values(array_filter([$terms === null ? 'deelnamevoorwaarden' : null, $privacy === null ? 'privacyverklaring' : null]));

        $items[] = [
            'key' => 'voorwaarden',
            'title' => 'Deelnamevoorwaarden en privacyverklaring',
            'text' => $missingTerms === [] ? 'Gepubliceerd: '.$terms->title.' en '.$privacy->title.'. Bakkers gaan hiermee akkoord bij het aanmelden.' : 'Nog niet gepubliceerd: '.implode(' en ', $missingTerms).'. Zonder gepubliceerde versie blijft de aanmeldpagina dicht, ook als de inschrijving open staat.'.($drafts->isNotEmpty() ? ' Er staat een concept klaar; met de knop publiceer je hem direct.' : ' Maak eerst een versie aan.'),
            'done' => $missingTerms === [],
            'optional' => false,
            'url' => TermsVersionResource::canViewAny() ? TermsVersionResource::getUrl('index') : null,
            'action' => $missingTerms === [] ? 'Bekijken' : ($drafts->isNotEmpty() ? 'Concept publiceren' : 'Versie aanmaken'),
            'icon' => 'heroicon-o-document-text',
        ];

        $model = $edition === null ? null : ScoringModel::query()->where('edition_id', $edition->getKey())->where('is_active', true)->with('criteria')->first();

        $items[] = [
            'key' => 'beoordelingsmodel',
            'title' => 'Beoordelingsmodel',
            'text' => $model === null ? 'Het panel heeft een actief model nodig: de onderdelen en hun punten (samen honderd). Nodig vóór de eerste testdag, niet voor de inschrijving.' : ($model->sumsToHundred() ? $model->name.' is actief en telt op tot honderd punten.' : $model->name.' is actief maar telt niet op tot honderd punten.'),
            'done' => $model !== null && $model->sumsToHundred(),
            'optional' => true,
            'url' => ScoringModelResource::canViewAny() ? ScoringModelResource::getUrl('index') : null,
            'action' => 'Model instellen',
            'icon' => 'heroicon-o-scale',
        ];

        $locations = TestLocation::query()->where('is_active', true)->count();

        $items[] = [
            'key' => 'testlocaties',
            'title' => 'Testlocaties en aanlevermomenten',
            'text' => $locations > 0 ? $locations.' actieve '.($locations === 1 ? 'locatie' : 'locaties').'. Maak per testdag aanlevermomenten aan, dan kunnen bakkers kiezen wanneer ze brengen.' : 'Waar bakkers hun oliebollen brengen. Nodig zodra de eerste bakker een aanlevermoment wil kiezen, niet voor de inschrijving.',
            'done' => $locations > 0,
            'optional' => true,
            'url' => TestLocationResource::canViewAny() ? TestLocationResource::getUrl('index') : null,
            'action' => 'Locaties instellen',
            'icon' => 'heroicon-o-map-pin',
        ];

        return $items;
    }

    /**
     * Hoeveel verplichte stappen klaar zijn, van het totaal.
     *
     * @return array{done: int, total: int}
     */
    public function progress(): array
    {
        $required = array_filter($this->items(), fn (array $item) => ! $item['optional']);

        return ['done' => count(array_filter($required, fn (array $item) => $item['done'])), 'total' => count($required)];
    }

    /**
     * Wat een bakker nodig heeft om zich aan te kunnen melden; zonder dit heeft openzetten geen zin.
     *
     * @return list<string>
     */
    public function blockers(): array
    {
        $byKey = collect($this->items())->keyBy('key');
        $reasons = [];

        if (! $byKey['editie']['done']) {
            $reasons[] = 'er is nog geen editie';
        }

        if (! $byKey['provincies']['done']) {
            $reasons[] = 'er zijn nog geen plekken per provincie';
        }

        if (! $byKey['pakketten']['done']) {
            $reasons[] = 'er is nog geen deelnameprijs (pakket)';
        }

        if (! $byKey['voorwaarden']['done']) {
            $reasons[] = 'de deelnamevoorwaarden of de privacyverklaring zijn nog niet gepubliceerd';
        }

        return $reasons;
    }

    /**
     * @return Registration
     */
    public function registration(): array
    {
        $edition = $this->edition;
        $url = route('aanmelden');

        if ($edition === null) {
            return ['state' => 'none', 'headline' => 'Er is nog geen editie', 'text' => 'Maak eerst de editie aan; daarna kun je hier de inschrijving openzetten.', 'reasons' => [], 'can_open' => false, 'can_close' => false, 'opens_at' => null, 'closes_at' => null, 'url' => $url];
        }

        $blockers = $this->blockers();
        $opens = $edition->registration_opens_at;
        $closes = $edition->registrationClosesAt();
        $opensLabel = $opens === null ? null : DutchTime::format($opens, 'dddd D MMMM [om] HH:mm');
        $closesLabel = $closes === null ? null : DutchTime::format($closes, 'dddd D MMMM [om] HH:mm');
        $dateSaysOpen = $edition->isRegistrationOpen();

        if ($dateSaysOpen && $blockers === []) {
            return ['state' => 'open', 'headline' => 'De inschrijving is open', 'text' => 'Bakkers kunnen zich aanmelden op '.$url.'.'.($closesLabel !== null ? ' De inschrijving sluit vanzelf op '.$closesLabel.'.' : ' Er is nog geen sluitdatum: zet die bij de editie, of sluit hier met de hand.'), 'reasons' => [], 'can_open' => false, 'can_close' => true, 'opens_at' => $opensLabel, 'closes_at' => $closesLabel, 'url' => $url];
        }

        if ($dateSaysOpen) {
            return ['state' => 'blocked', 'headline' => 'De inschrijving staat open, maar de aanmeldpagina is dicht', 'text' => 'De datums zeggen open, maar er ontbreekt nog iets. Zodra dat klaar is, gaat de aanmeldpagina vanzelf open.', 'reasons' => $blockers, 'can_open' => false, 'can_close' => true, 'opens_at' => $opensLabel, 'closes_at' => $closesLabel, 'url' => $url];
        }

        $reasons = $blockers;

        if (in_array($edition->status, [EditionStatus::Closed, EditionStatus::Frozen, EditionStatus::Published, EditionStatus::Archived], true)) {
            $reasons[] = 'de editie heeft status '.$edition->status->getLabel().'; zet die terug naar Inschrijving open of Testfase om te heropenen';
        } elseif ($edition->registrationHasClosed()) {
            $reasons[] = 'de inschrijving is gesloten op '.$closesLabel;
        } elseif ($opens === null) {
            $reasons[] = 'er is nog geen openingsdatum';
        } else {
            $reasons[] = 'de inschrijving opent vanzelf op '.$opensLabel;
        }

        $canOpen = $blockers === [] && ! in_array($edition->status, [EditionStatus::Frozen, EditionStatus::Published, EditionStatus::Archived], true);

        return ['state' => 'closed', 'headline' => 'De inschrijving is dicht', 'text' => $canOpen ? 'Alles staat klaar. Met de knop zet je de inschrijving nu open; de aanmeldpagina gaat dan direct aan.' : 'Eerst de punten hieronder afmaken, dan kan de inschrijving open.', 'reasons' => $reasons, 'can_open' => $canOpen, 'can_close' => false, 'opens_at' => $opensLabel, 'closes_at' => $closesLabel, 'url' => $url];
    }
}
