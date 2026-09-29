<?php

namespace App\Filament\Support;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Filament\Pages\Intake;
use App\Filament\Resources\Entries\EntryResource;
use App\Filament\Resources\PublicationBatches\PublicationBatchResource;
use App\Filament\Resources\Samples\SampleResource;
use App\Filament\Resources\TestSessions\TestSessionResource;
use App\Support\DutchTime;
use Carbon\CarbonImmutable;

/**
 * Waar staan we in het seizoen: de fase op basis van de editiedatums en de negen
 * processtappen uit docs/04 §1 met het aantal inschrijvingen dat nu in die stap zit.
 */
final class Season
{
    /**
     * Fase => welke menugroep standaard open staat.
     */
    private const array PHASE_GROUPS = [
        'voorbereiding' => 'Bakkers',
        'inschrijving' => 'Bakkers',
        'testdagen' => 'Testdagen',
        'publicatie' => 'Uitslag',
        'finale' => 'Uitslag',
        'campagne' => 'Campagne',
        'afgerond' => 'Bakkers',
        'geen' => 'Bakkers',
    ];

    /**
     * Volgorde van de fasen; processtap 1-2 hoort bij inschrijving, 3-7 bij de testdagen, 8-9 bij publicatie.
     */
    private const array PHASE_ORDER = ['voorbereiding', 'inschrijving', 'testdagen', 'publicatie', 'finale', 'campagne', 'afgerond'];

    /** @var array{key: string, label: string, note: string}|null */
    private ?array $phase = null;

    public function __construct(private readonly ?Edition $edition, private readonly CarbonImmutable $now) {}

    /**
     * @return array{key: string, label: string, note: string}
     */
    public function phase(): array
    {
        return $this->phase ??= $this->resolvePhase();
    }

    public function menuGroup(): string
    {
        return self::PHASE_GROUPS[$this->phase()['key']];
    }

    /**
     * De negen stappen met status (done, current, running, upcoming), aantal en link.
     *
     * @return list<array{n: int, label: string, count: int|null, state: string, url: string|null, note: string|null}>
     */
    public function steps(): array
    {
        $edition = $this->edition;
        $phaseIndex = array_search($this->phase()['key'], self::PHASE_ORDER, true);
        $phaseIndex = $phaseIndex === false ? -1 : $phaseIndex;

        $byStatus = $edition === null
            ? collect()
            : Entry::query()->where('edition_id', $edition->getKey())->selectRaw('status, count(*) as aantal')->groupBy('status')->pluck('aantal', 'status');
        $count = fn (EntryStatus ...$statuses): int => (int) collect($statuses)->sum(fn (EntryStatus $status) => (int) ($byStatus[$status->value] ?? 0));
        $inSession = $edition === null ? 0 : Sample::query()->where('edition_id', $edition->getKey())->where('status', SampleStatus::Scheduled)->count();

        $canSeeEntries = EntryResource::canViewAny();
        $entriesUrl = fn (EntryStatus ...$statuses): ?string => $canSeeEntries
            ? EntryResource::getUrl('index', ['filters' => ['status' => ['values' => array_map(fn (EntryStatus $status) => $status->value, $statuses)]]])
            : null;

        $definition = [
            [1, 'Aanmelding', $count(EntryStatus::Registered), $entriesUrl(EntryStatus::Registered), 1],
            [2, 'Inplannen', $count(EntryStatus::Scheduled), $entriesUrl(EntryStatus::Scheduled), 1],
            [3, 'Ontvangst', $count(EntryStatus::Received), Intake::canAccess() ? Intake::getUrl() : $entriesUrl(EntryStatus::Received), 2],
            [4, 'Registratie', $count(EntryStatus::Numbered), $entriesUrl(EntryStatus::Numbered), 2],
            [5, 'Sessie', $inSession, TestSessionResource::canViewAny() ? TestSessionResource::getUrl('index') : null, 2],
            [6, 'Beoordelen', $count(EntryStatus::Scored), SampleResource::canViewAny() ? SampleResource::getUrl('index') : null, 2],
            [7, 'Scorecontrole', $count(EntryStatus::Reviewed), SampleResource::canViewAny() ? SampleResource::getUrl('index', ['filters' => ['needs_attention' => ['isActive' => true]]]) : null, 2],
            [8, 'Koppeling', $count(EntryStatus::Linked), $entriesUrl(EntryStatus::Linked), 3],
            [9, 'Publicatie', $count(EntryStatus::Published, EntryStatus::Confidential), PublicationBatchResource::canViewAny() ? PublicationBatchResource::getUrl('index') : null, 3],
        ];

        $steps = [];

        foreach ($definition as [$n, $label, $stepCount, $url, $stepPhase]) {
            $state = match (true) {
                $phaseIndex > 3 => 'done',
                $phaseIndex < $stepPhase => 'upcoming',
                $phaseIndex === $stepPhase => 'current',
                default => 'done',
            };

            // Tijdens de testdagen lopen koppeling en publicatie al mee (batches op dinsdag en vrijdag).
            if ($state === 'upcoming' && $phaseIndex === 2 && $stepPhase === 3) {
                $state = 'running';
            }

            $steps[] = [
                'n' => $n,
                'label' => $label,
                'count' => $edition === null ? null : $stepCount,
                'state' => $state,
                'url' => $url,
                'note' => $state === 'running' ? 'di en vr 12:00' : null,
            ];
        }

        return $steps;
    }

    /**
     * @return array{key: string, label: string, note: string}
     */
    private function resolvePhase(): array
    {
        $edition = $this->edition;

        if ($edition === null) {
            return ['key' => 'geen', 'label' => 'Geen actieve editie', 'note' => 'Maak een editie aan bij Instellingen om het seizoen te starten.'];
        }

        $now = $this->now->utc();
        $endOfDay = fn ($date) => $date === null ? null : DutchTime::display($date)->endOfDay()->utc();
        $startOfDay = fn ($date) => $date === null ? null : DutchTime::display($date)->startOfDay()->utc();
        $fmt = fn ($date, string $format = 'dddd D MMMM'): string => (string) DutchTime::format($date, $format);

        $opens = $edition->registration_opens_at;
        $closes = $edition->registration_closes_at;
        $firstTest = $startOfDay($edition->first_test_day);
        $lastTest = $endOfDay($edition->last_test_day);
        $mainPublication = $edition->main_publication_at;
        $national = $edition->national_result_at;
        $lastRedeem = $endOfDay($edition->last_redeem_day);

        $phases = [
            'voorbereiding' => [
                'key' => 'voorbereiding',
                'label' => 'Voorbereiding',
                'note' => $opens === null ? 'Stel de inschrijfdatums in bij Instellingen.' : 'De inschrijving opent '.$fmt($opens, 'dddd D MMMM [om] HH:mm').'.',
            ],
            'inschrijving' => [
                'key' => 'inschrijving',
                'label' => 'Inschrijving loopt',
                'note' => ($closes !== null && $now->lt($closes) ? 'Sluit '.$fmt($closes).' · ' : '').($edition->first_test_day !== null ? 'eerste testdag '.$fmt($edition->first_test_day).'.' : 'testdagen nog niet ingesteld.'),
            ],
            'testdagen' => [
                'key' => 'testdagen',
                'label' => 'Testdagen',
                'note' => ($edition->last_test_day !== null ? 'Tot en met '.$fmt($edition->last_test_day).' · ' : '').'publicaties dinsdag en vrijdag om 12:00.',
            ],
            'publicatie' => [
                'key' => 'publicatie',
                'label' => 'Laatste publicaties en bevriezing',
                'note' => ($edition->freeze_at !== null ? 'Bevriezing '.$fmt($edition->freeze_at, 'dddd D MMMM [om] HH:mm').' · ' : '').($mainPublication !== null ? 'hoofdpublicatie '.$fmt($mainPublication, 'dddd D MMMM [om] HH:mm').'.' : ''),
            ],
            'finale' => [
                'key' => 'finale',
                'label' => 'Finale',
                'note' => ($edition->final_test_day !== null ? 'Finaletest '.$fmt($edition->final_test_day).' · ' : '').($national !== null ? 'landelijke uitslag '.$fmt($national, 'dddd D MMMM [om] HH:mm').'.' : ''),
            ],
            'campagne' => [
                'key' => 'campagne',
                'label' => 'Campagne',
                'note' => $edition->last_redeem_day !== null ? 'Cadeaubonnen zijn te verzilveren tot en met '.$fmt($edition->last_redeem_day).'.' : 'Badges, socialkits en cadeaubonnen zijn live.',
            ],
            'afgerond' => [
                'key' => 'afgerond',
                'label' => "Editie {$edition->year} is afgerond",
                'note' => 'Bekijk de uitslag of maak een nieuwe editie aan bij Instellingen.',
            ],
        ];

        // De status die de organisatie zelf zet gaat voor; alleen bij "gesloten" en "gepubliceerd" beslissen de datums.
        $byStatus = match ($edition->status) {
            EditionStatus::Draft => 'voorbereiding',
            EditionStatus::RegistrationOpen => 'inschrijving',
            EditionStatus::Testing => 'testdagen',
            EditionStatus::Frozen => 'publicatie',
            EditionStatus::Archived => 'afgerond',
            default => null,
        };

        if ($byStatus !== null) {
            return $phases[$byStatus];
        }

        $byDate = match (true) {
            $opens !== null && $now->lt($opens) => 'voorbereiding',
            $firstTest !== null && $now->lt($firstTest) => 'inschrijving',
            $lastTest !== null && $now->lte($lastTest) => 'testdagen',
            $mainPublication !== null && $now->lt($mainPublication) => 'publicatie',
            $national !== null && $now->lt($national) => 'finale',
            $lastRedeem !== null && $now->lte($lastRedeem) => 'campagne',
            default => 'afgerond',
        };

        return $phases[$byDate];
    }
}
