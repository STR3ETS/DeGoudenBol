<?php

namespace App\Filament\Support;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TestLocation;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\TestSession;
use App\Filament\Pages\Intake;
use App\Filament\Resources\DeliverySlots\DeliverySlotResource;
use App\Filament\Resources\Samples\SampleResource;
use App\Filament\Resources\TestSessions\TestSessionResource;
use App\Support\DutchTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Alles van één testdag: leveringen per slot, sessies met voortgang, versheidsklokken en de
 * tijdlijn. Wat je ziet volgt de rol: leveringen (met bedrijfsnamen) alleen voor ontvangst,
 * sessies en monsters (alleen testnummers) voor coördinatie en scorecontrole.
 *
 * @phpstan-type Block array{left: float, width: float, title: string, sub: string, tone: string, url: string|null}
 */
final class TestDayData
{
    private const array STATUSES_RECEIVED = [
        EntryStatus::Received, EntryStatus::Numbered, EntryStatus::Scored, EntryStatus::Reviewed,
        EntryStatus::Linked, EntryStatus::Published, EntryStatus::Confidential,
    ];

    /** @var Collection<int, DeliverySlot>|null */
    private ?Collection $slots = null;

    /** @var Collection<int, TestSession>|null */
    private ?Collection $sessions = null;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $samples = null;

    /** @var array<int, string>|null */
    private ?array $locations = null;

    public function __construct(
        private readonly ?Edition $edition,
        private readonly CarbonImmutable $day,
        private readonly CarbonImmutable $now,
    ) {}

    public function day(): CarbonImmutable
    {
        return $this->day;
    }

    public function isToday(): bool
    {
        return $this->day->isSameDay($this->now);
    }

    /**
     * Slots met aantallen bevatten geen namen; wie aanleverslots mag zien, ziet ze.
     */
    public function showsDeliveries(): bool
    {
        return DeliverySlotResource::canViewAny();
    }

    /**
     * Doorklikken naar ontvangst kan alleen wie mag ontvangen (daar staan wél namen).
     */
    public function intakeUrl(): ?string
    {
        return Intake::canAccess() ? Intake::getUrl() : null;
    }

    public function showsSessions(): bool
    {
        return TestSessionResource::canViewAny();
    }

    public function showsSamples(): bool
    {
        return SampleResource::canViewAny();
    }

    public function isEmpty(): bool
    {
        return $this->slots()->isEmpty() && $this->sessions()->isEmpty() && $this->samples()->isEmpty();
    }

    /**
     * Aanleverslots van deze dag met verwacht en ontvangen aantal.
     *
     * @return Collection<int, DeliverySlot>
     */
    public function slots(): Collection
    {
        if ($this->slots !== null) {
            return $this->slots;
        }

        if ($this->edition === null || ! $this->showsDeliveries()) {
            return $this->slots = collect();
        }

        return $this->slots = DeliverySlot::query()
            ->where('edition_id', $this->edition->getKey())
            ->whereBetween('starts_at', $this->utcRange())
            ->withCount([
                'entries as expected_count' => fn (Builder $query) => $query->where('status', EntryStatus::Scheduled),
                'entries as received_count' => fn (Builder $query) => $query->whereIn('status', self::STATUSES_RECEIVED),
            ])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Sessies van deze dag met aantallen monsters, panelleden, uitserveringen en ingediende kaarten.
     *
     * @return Collection<int, TestSession>
     */
    public function sessions(): Collection
    {
        if ($this->sessions !== null) {
            return $this->sessions;
        }

        if ($this->edition === null || ! $this->showsSessions()) {
            return $this->sessions = collect();
        }

        return $this->sessions = TestSession::query()
            ->where('edition_id', $this->edition->getKey())
            ->whereBetween('starts_at', $this->utcRange())
            ->withCount(['samples', 'panelists', 'assignments', 'scorecards'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Monsters die vandaag zijn ontvangen of vandaag verlopen, op versheid gesorteerd.
     *
     * @return Collection<int, array{sample: Sample, number: string, received: string|null, expires: string|null, minutes: int|null, state: string, scored: bool, sessions: string, url: string}>
     */
    public function samples(): Collection
    {
        if ($this->samples !== null) {
            return $this->samples;
        }

        if ($this->edition === null || ! $this->showsSamples()) {
            return $this->samples = collect();
        }

        [$start, $end] = $this->utcRange();

        $samples = Sample::query()
            ->where('edition_id', $this->edition->getKey())
            ->whereHas('intake', fn (Builder $query) => $query->where(fn (Builder $intake) => $intake
                ->whereBetween('received_at', [$start, $end])
                ->orWhereBetween('freshness_expires_at', [$start, $end])))
            ->with(['intake', 'sessions', 'result'])
            ->get();

        return $this->samples = $samples
            ->map(function (Sample $sample): array {
                $expiresAt = $sample->intake?->freshness_expires_at;
                $minutes = $expiresAt === null ? null : (int) $this->now->diffInMinutes($expiresAt, false);
                $scored = in_array($sample->status, [SampleStatus::Scored, SampleStatus::Final], true) || $sample->result !== null;

                $state = match (true) {
                    $sample->status === SampleStatus::FreshnessExpired, $sample->status === SampleStatus::Void => 'verlopen',
                    $scored => 'beoordeeld',
                    $minutes !== null && $minutes < 0 => 'verlopen',
                    $minutes !== null && $minutes <= 60 => 'bijna',
                    default => 'vers',
                };

                return [
                    'sample' => $sample,
                    'number' => $sample->label(),
                    'received' => $sample->intake?->received_at ? DutchTime::format($sample->intake->received_at, 'HH:mm') : null,
                    'expires' => $expiresAt ? DutchTime::format($expiresAt, 'HH:mm') : null,
                    'minutes' => $minutes,
                    'state' => $state,
                    'scored' => $scored,
                    'sessions' => $sample->sessions->map(fn (TestSession $session) => $session->displayName())->implode(', '),
                    'url' => SampleResource::getUrl('view', ['record' => $sample]),
                ];
            })
            ->sortBy(fn (array $row) => [$row['state'] === 'beoordeeld' ? 1 : 0, $row['minutes'] ?? PHP_INT_MAX])
            ->values();
    }

    /**
     * @return array{deliveries_expected: int, deliveries_received: int, samples: int, sessions_planned: int, sessions_running: int, sessions_closed: int, panelists: int, cards_submitted: int, cards_expected: int}
     */
    public function kpis(): array
    {
        $sessions = $this->sessions();

        $panelists = $sessions->isEmpty()
            ? 0
            : TestSession::query()->whereKey($sessions->modelKeys())->with('panelists:id')->get()
                ->flatMap(fn (TestSession $session) => $session->panelists->modelKeys())
                ->unique()
                ->count();

        return [
            'deliveries_expected' => (int) $this->slots()->sum('expected_count') + (int) $this->slots()->sum('received_count'),
            'deliveries_received' => (int) $this->slots()->sum('received_count'),
            'samples' => $this->samples()->filter(fn (array $row) => $row['received'] !== null)->count(),
            'sessions_planned' => $sessions->where('status', SessionStatus::Planned)->count(),
            'sessions_running' => $sessions->where('status', SessionStatus::Running)->count(),
            'sessions_closed' => $sessions->where('status', SessionStatus::Closed)->count(),
            'panelists' => $panelists,
            'cards_submitted' => (int) $sessions->sum('scorecards_count'),
            'cards_expected' => (int) $sessions->sum('assignments_count'),
        ];
    }

    public function locationName(?int $locationId): ?string
    {
        if ($locationId === null) {
            return null;
        }

        $this->locations ??= TestLocation::query()->pluck('name', 'id')->all();

        return $this->locations[$locationId] ?? null;
    }

    /**
     * Uren van de tijdlijn: van 08:00 (of eerder) tot 18:00 (of later), zodat alles past.
     *
     * @return array{start: CarbonImmutable, end: CarbonImmutable, hours: list<string>}
     */
    public function window(): array
    {
        $start = $this->day->setTime(8, 0);
        $end = $this->day->setTime(18, 0);

        foreach ($this->slots() as $slot) {
            $start = $start->min(DutchTime::display($slot->starts_at)->startOfHour());
            $end = $end->max(DutchTime::display($slot->ends_at)->addMinute()->ceilHour());
        }

        foreach ($this->sessions() as $session) {
            $start = $start->min(DutchTime::display($session->starts_at)->startOfHour());
            $end = $end->max(DutchTime::display($session->ends_at ?? $session->starts_at->addHours(2))->addMinute()->ceilHour());
        }

        $hours = [];

        for ($hour = $start; $hour->lt($end); $hour = $hour->addHour()) {
            $hours[] = $hour->format('H:i');
        }

        return ['start' => $start, 'end' => $end, 'hours' => $hours];
    }

    /**
     * Banen van de tijdlijn: eerst de leveringen, dan iedere sessie.
     *
     * @return list<array{label: string, sub: string|null, blocks: list<Block>}>
     */
    public function lanes(): array
    {
        $lanes = [];

        if ($this->slots()->isNotEmpty()) {
            $lanes[] = [
                'label' => 'Aanlevering',
                'sub' => $this->slots()->count().' '.($this->slots()->count() === 1 ? 'slot' : 'slots'),
                'blocks' => $this->slots()->map(function (DeliverySlot $slot): array {
                    $expected = (int) $slot->expected_count;
                    $received = (int) $slot->received_count;

                    return $this->block(
                        $slot->starts_at,
                        $slot->ends_at,
                        DutchTime::format($slot->starts_at, 'HH:mm').' – '.DutchTime::format($slot->ends_at, 'HH:mm'),
                        "{$received} van ".($expected + $received).' ontvangen',
                        $expected === 0 ? ($received > 0 ? 'succes' : 'neutraal') : 'goud',
                        $this->intakeUrl(),
                    );
                })->values()->all(),
            ];
        }

        foreach ($this->sessions() as $session) {
            $lanes[] = [
                'label' => $session->displayName(),
                'sub' => $this->locationName($session->test_location_id),
                'blocks' => [$this->block(
                    $session->starts_at,
                    $session->ends_at ?? $session->starts_at->addHours(2),
                    DutchTime::format($session->starts_at, 'HH:mm').' – '.DutchTime::format($session->ends_at ?? $session->starts_at->addHours(2), 'HH:mm'),
                    "{$session->samples_count} monsters · {$session->panelists_count} panel · ".$session->status->getLabel(),
                    match ($session->status) {
                        SessionStatus::Running => 'goud',
                        SessionStatus::Closed => 'succes',
                        SessionStatus::Cancelled => 'neutraal',
                        default => 'info',
                    },
                    TestSessionResource::getUrl('view', ['record' => $session]),
                )],
            ];
        }

        return $lanes;
    }

    /**
     * Positie van "nu" op de tijdlijn in procenten, alleen op de dag zelf en binnen het venster.
     */
    public function nowPosition(): ?float
    {
        if (! $this->isToday()) {
            return null;
        }

        $window = $this->window();

        if ($this->now->lt($window['start']) || $this->now->gt($window['end'])) {
            return null;
        }

        return $this->percentage($this->now, $window);
    }

    /**
     * Eerstvolgende dag met een sessie of slot, om naartoe te springen als deze dag leeg is.
     */
    public function nextTestDay(): ?CarbonImmutable
    {
        if ($this->edition === null) {
            return null;
        }

        $from = $this->day->endOfDay()->utc();

        $candidates = collect([
            $this->showsSessions() ? TestSession::query()->where('edition_id', $this->edition->getKey())->where('starts_at', '>', $from)->min('starts_at') : null,
            $this->showsDeliveries() ? DeliverySlot::query()->where('edition_id', $this->edition->getKey())->where('starts_at', '>', $from)->min('starts_at') : null,
        ])->filter();

        if ($candidates->isEmpty()) {
            return null;
        }

        return DutchTime::display(CarbonImmutable::parse($candidates->min()))->startOfDay();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function utcRange(): array
    {
        return [$this->day->startOfDay()->utc(), $this->day->endOfDay()->utc()];
    }

    /**
     * @return Block
     */
    private function block(CarbonImmutable $start, CarbonImmutable $end, string $title, string $sub, string $tone, ?string $url): array
    {
        $window = $this->window();
        $left = $this->percentage($start, $window);
        $right = $this->percentage($end, $window);
        $width = max(4.0, $right - $left);

        // Een smal blok (een uur op een lange dag) toont alleen de begintijd; de rest staat in de tooltip.
        if ($width < 12) {
            $title = DutchTime::format($start, 'HH:mm');
        }

        return [
            'left' => $left,
            'width' => $width,
            'title' => $title,
            'sub' => $sub,
            'tone' => $tone,
            'url' => $url,
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $window
     */
    private function percentage(CarbonImmutable $moment, array $window): float
    {
        $total = max(1, $window['start']->diffInMinutes($window['end']));
        $offset = $window['start']->diffInMinutes(DutchTime::display($moment), false);

        return round(max(0.0, min(100.0, $offset / $total * 100)), 2);
    }
}
