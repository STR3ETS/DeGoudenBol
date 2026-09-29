<?php

namespace App\Filament\Support;

use App\Domain\Charities\Models\CharityPayout;
use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Enums\ModerationStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\Profile;
use App\Domain\Participants\Services\ProvinceCapacity;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Editions\EditionResource;
use App\Filament\Resources\Entries\EntryResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\NewsPosts\NewsPostResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\ParticipantUsers\ParticipantUserResource;
use App\Filament\Resources\Profiles\ProfileResource;
use App\Models\User;
use App\Support\DutchTime;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Alles wat het dashboard toont, in één keer berekend. Secties volgen de rol van de medewerker.
 */
final class DashboardData
{
    private ?Edition $edition;

    private CarbonImmutable $now;

    private ?Season $season = null;

    private ?WorkQueue $work = null;

    public function __construct(private readonly User $user, private readonly ProvinceCapacity $capacity)
    {
        $this->edition = Edition::query()->current()->with('provinces')->first();
        $this->now = DutchTime::display(now());
    }

    public function edition(): ?Edition
    {
        return $this->edition;
    }

    public function greeting(): string
    {
        $hour = (int) $this->now->format('G');
        $word = match (true) {
            $hour < 6 => 'Goedenacht',
            $hour < 12 => 'Goedemorgen',
            $hour < 18 => 'Goedemiddag',
            default => 'Goedenavond',
        };

        $firstName = explode(' ', trim($this->user->name))[0] ?: $this->user->name;

        return "{$word}, {$firstName}";
    }

    public function subheading(): string
    {
        $date = ucfirst($this->now->locale('nl')->isoFormat('dddd D MMMM YYYY'));

        if ($this->edition === null) {
            return "{$date} · geen actieve editie";
        }

        return "{$date} · De Gouden Bol {$this->edition->year} · {$this->edition->status->getLabel()}";
    }

    /**
     * @return list<array{label: string, value: string, hint: string, icon: string, url: string|null, tone: string}>
     */
    public function kpis(): array
    {
        if ($this->edition === null) {
            return [];
        }

        $entries = Entry::query()->where('edition_id', $this->edition->getKey());
        $confirmed = (clone $entries)->confirmed()->count();
        $pending = (clone $entries)->where('status', EntryStatus::PendingPayment)->where('reservation_expires_at', '>', now())->count();
        $capacity = $this->edition->provinces->sum(fn ($province) => (int) ($province->pivot->capacity ?? $this->edition->settings->capacityPerProvince));
        $revenue = (int) Order::query()->where('edition_id', $this->edition->getKey())->where('status', OrderStatus::Paid)->sum('subtotal_cents');
        $pendingProfiles = Profile::query()->where('moderation_status', ModerationStatus::Pending)->count();
        $today = (clone $entries)->confirmed()->where('confirmed_at', '>=', $this->now->startOfDay()->utc())->count();

        $kpis = [
            [
                'label' => 'Bevestigde deelnemers',
                'value' => (string) $confirmed,
                'hint' => "van {$capacity} plekken".($today > 0 ? " · {$today} vandaag" : ''),
                'icon' => 'heroicon-o-clipboard-document-check',
                'url' => EntryResource::canViewAny() ? EntryResource::getUrl('index') : null,
                'tone' => 'goud',
            ],
            [
                'label' => 'Wacht op betaling',
                'value' => (string) $pending,
                'hint' => 'reservering nog geldig',
                'icon' => 'heroicon-o-clock',
                'url' => EntryResource::canViewAny() ? EntryResource::getUrl('index', ['filters' => ['status' => ['values' => [EntryStatus::PendingPayment->value]]]]) : null,
                'tone' => 'espresso',
            ],
        ];

        if (OrderResource::canViewAny()) {
            $kpis[] = [
                'label' => 'Omzet deelname',
                'value' => Money::format($revenue),
                'hint' => 'excl. btw · betaalde orders',
                'icon' => 'heroicon-o-banknotes',
                'url' => OrderResource::getUrl('index'),
                'tone' => 'succes',
            ];
        }

        if (ProfileResource::canViewAny()) {
            $kpis[] = [
                'label' => 'Profielen te modereren',
                'value' => (string) $pendingProfiles,
                'hint' => $pendingProfiles > 0 ? 'wachten op de redactie' : 'alles is beoordeeld',
                'icon' => 'heroicon-o-document-check',
                'url' => ProfileResource::getUrl('index'),
                'tone' => $pendingProfiles > 0 ? 'waarschuwing' : 'info',
            ];
        }

        return $kpis;
    }

    /**
     * Financiën per inkomstenstroom (deelname, sponsoring) en de stand van de 10%-reservering.
     *
     * @return array{streams: list<array{label: string, paid_cents: int, open_cents: int, open_count: int, url: string}>, paid_cents: int, charity_percentage: int, charity_reserved_cents: int, charity_paid_cents: int, charity_open_cents: int, charity_pot_cents: int}|null
     */
    public function finance(): ?array
    {
        if ($this->edition === null || ! OrderResource::canViewAny()) {
            return null;
        }

        $orders = Order::query()->where('edition_id', $this->edition->getKey());
        $streams = [];

        foreach ([['Deelname', Entry::class], ['Sponsoring', Sponsor::class]] as [$label, $type]) {
            $paid = (int) (clone $orders)->where('orderable_type', $type)->where('status', OrderStatus::Paid)->sum('subtotal_cents');
            $open = (clone $orders)->where('orderable_type', $type)->where('status', OrderStatus::Pending);

            $streams[] = [
                'label' => $label,
                'paid_cents' => $paid,
                'open_cents' => (int) (clone $open)->sum('subtotal_cents'),
                'open_count' => (clone $open)->count(),
                'url' => OrderResource::getUrl('index', ['filters' => ['status' => ['value' => OrderStatus::Paid->value]]]),
            ];
        }

        $reservations = CharityReservation::query()->where('edition_id', $this->edition->getKey());
        $reserved = (int) (clone $reservations)->sum('amount_cents');
        $pot = (int) (clone $reservations)->whereNull('charity_id')->sum('amount_cents');
        $paidOut = (int) CharityPayout::query()->where('edition_id', $this->edition->getKey())->sum('amount_cents');

        return [
            'streams' => $streams,
            'paid_cents' => (int) array_sum(array_column($streams, 'paid_cents')),
            'charity_percentage' => $this->edition->settings->charityPercentage,
            'charity_reserved_cents' => $reserved,
            'charity_paid_cents' => $paidOut,
            'charity_open_cents' => max(0, $reserved - $paidOut),
            'charity_pot_cents' => $pot,
        ];
    }

    /**
     * Openstaande acties met aantallen en directe links.
     *
     * @return list<array{title: string, text: string, count: int, url: string, icon: string, tone: string}>
     */
    public function actions(): array
    {
        if ($this->edition === null) {
            return [];
        }

        $actions = [];

        if (ProfileResource::canViewAny()) {
            $count = Profile::query()->where('moderation_status', ModerationStatus::Pending)->count();

            if ($count > 0) {
                $actions[] = ['title' => 'Profielteksten beoordelen', 'text' => 'Ingediend door deelnemers, nog niet op de site.', 'count' => $count, 'url' => ProfileResource::getUrl('index'), 'icon' => 'heroicon-o-document-check', 'tone' => 'waarschuwing'];
            }
        }

        if (OrderResource::canViewAny()) {
            $count = Order::query()->where('edition_id', $this->edition->getKey())->where('status', OrderStatus::Pending)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count();

            if ($count > 0) {
                $actions[] = ['title' => 'Openstaande orders', 'text' => 'Betaling nog niet ontvangen; bankoverschrijving handmatig afboeken.', 'count' => $count, 'url' => OrderResource::getUrl('index', ['filters' => ['status' => ['value' => OrderStatus::Pending->value]]]), 'icon' => 'heroicon-o-shopping-bag', 'tone' => 'info'];
            }
        }

        if (EntryResource::canViewAny()) {
            $count = Entry::query()->where('edition_id', $this->edition->getKey())->confirmed()
                ->whereHas('company.primaryLocation', fn ($q) => $q->whereNull('lat'))->count();

            if ($count > 0) {
                $actions[] = ['title' => 'Adressen zonder coördinaten', 'text' => 'Nodig voor de kaart en de provinciebepaling.', 'count' => $count, 'url' => CompanyResource::canViewAny() ? CompanyResource::getUrl('index') : EntryResource::getUrl('index'), 'icon' => 'heroicon-o-map-pin', 'tone' => 'neutraal'];
            }
        }

        return $actions;
    }

    /**
     * @return Collection<int, Entry>
     */
    public function recentEntries(int $limit = 8): Collection
    {
        if ($this->edition === null || ! EntryResource::canViewAny()) {
            return collect();
        }

        return Entry::query()
            ->where('edition_id', $this->edition->getKey())
            ->with(['province', 'company'])
            ->orderByDesc('registered_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Aanmeldingen per dag over de afgelopen periode (Amsterdamse dagen), voor de staafjes op het dashboard.
     *
     * @return array{days: list<array{label: string, date: string, count: int, is_today: bool}>, total: int, max: int}
     */
    public function registrationTrend(int $days = 14): array
    {
        $empty = ['days' => [], 'total' => 0, 'max' => 0];

        if ($this->edition === null || ! EntryResource::canViewAny()) {
            return $empty;
        }

        $start = $this->now->startOfDay()->subDays($days - 1);

        $perDay = Entry::query()
            ->where('edition_id', $this->edition->getKey())
            ->where('registered_at', '>=', $start->utc())
            ->pluck('registered_at')
            ->map(fn ($at) => DutchTime::display($at)->format('Y-m-d'))
            ->countBy();

        $rows = [];
        $total = 0;

        for ($i = 0; $i < $days; $i++) {
            $day = $start->addDays($i);
            $count = (int) ($perDay[$day->format('Y-m-d')] ?? 0);
            $total += $count;

            $rows[] = [
                'label' => $day->locale('nl')->isoFormat('dd D MMM'),
                'date' => $day->format('Y-m-d'),
                'count' => $count,
                'is_today' => $i === $days - 1,
            ];
        }

        return ['days' => $rows, 'total' => $total, 'max' => max(array_column($rows, 'count') ?: [0])];
    }

    /**
     * @return list<array{name: string, slug: string, capacity: int, taken: int, available: int, percentage: int}>
     */
    public function provinces(): array
    {
        if ($this->edition === null) {
            return [];
        }

        $overview = $this->capacity->overview($this->edition);
        $rows = [];

        foreach ($this->edition->provinces as $province) {
            $stats = $overview[$province->slug] ?? ['capacity' => 0, 'taken' => 0, 'available' => 0];

            $rows[] = [
                'name' => $province->name,
                'slug' => $province->slug,
                ...$stats,
                'percentage' => $stats['capacity'] > 0 ? (int) round($stats['taken'] / $stats['capacity'] * 100) : 0,
            ];
        }

        return $rows;
    }

    /**
     * Mijlpalen van de editie met het aantal dagen tot dan; de eerstvolgende is gemarkeerd.
     * Naast de volledige datum ook de losse delen voor het datumblok op het dashboard.
     *
     * @return list<array{label: string, date: string, weekday: string, day: string, month: string, time: string|null, days: int, is_next: bool, is_past: bool}>
     */
    public function milestones(): array
    {
        if ($this->edition === null) {
            return [];
        }

        $edition = $this->edition;
        $candidates = [
            ['Inschrijving opent', $edition->registration_opens_at],
            ['Eerste testdag', $edition->first_test_day],
            ['Laatste testdag', $edition->last_test_day],
            ['Bevriezing', $edition->freeze_at],
            ['Hoofdpublicatie', $edition->main_publication_at],
            ['Finaletest', $edition->final_test_day],
            ['Landelijke uitslag', $edition->national_result_at],
            ['Laatste verzilverdag', $edition->last_redeem_day],
        ];

        $rows = [];
        $nextMarked = false;
        $today = $this->now->startOfDay();

        foreach ($candidates as [$label, $date]) {
            if ($date === null) {
                continue;
            }

            $local = DutchTime::display($date)->startOfDay();
            $days = (int) $today->diffInDays($local, false);
            $isPast = $days < 0;
            $isNext = ! $isPast && ! $nextMarked;

            if ($isNext) {
                $nextMarked = true;
            }

            // Datumvelden staan op middernacht UTC; alleen echte tijdstippen tonen we als tijd.
            $hasTime = $date->format('H:i') !== '00:00';

            $rows[] = [
                'label' => $label,
                'date' => DutchTime::format($date, $hasTime ? 'dd D MMM, HH:mm' : 'dd D MMM'),
                'weekday' => (string) DutchTime::format($date, 'dd'),
                'day' => (string) DutchTime::format($date, 'D'),
                'month' => (string) DutchTime::format($date, 'MMM'),
                'time' => $hasTime ? DutchTime::format($date, 'HH:mm') : null,
                'days' => $days,
                'is_next' => $isNext,
                'is_past' => $isPast,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, url: string, icon: string}>
     */
    public function quickLinks(): array
    {
        $links = [];

        foreach ([
            [EntryResource::class, 'Inschrijvingen', 'heroicon-o-clipboard-document-check'],
            [CompanyResource::class, 'Bedrijven', 'heroicon-o-building-storefront'],
            [ProfileResource::class, 'Profielmoderatie', 'heroicon-o-document-check'],
            [OrderResource::class, 'Orders', 'heroicon-o-shopping-bag'],
            [InvoiceResource::class, 'Facturen', 'heroicon-o-document-currency-euro'],
            [ParticipantUserResource::class, 'Deelnemersaccounts', 'heroicon-o-user-circle'],
            [NewsPostResource::class, 'Nieuws', 'heroicon-o-newspaper'],
            [EditionResource::class, 'Editie-instellingen', 'heroicon-o-calendar-days'],
        ] as [$resource, $label, $icon]) {
            if ($resource::canViewAny()) {
                $links[] = ['label' => $label, 'url' => $resource::getUrl('index'), 'icon' => $icon];
            }
        }

        return $links;
    }

    /**
     * Fase van het seizoen en de negen processtappen met aantallen.
     */
    public function season(): Season
    {
        return $this->season ??= new Season($this->edition, $this->now);
    }

    /**
     * "Jouw werk vandaag" voor de ingelogde medewerker.
     */
    public function work(): WorkQueue
    {
        return $this->work ??= new WorkQueue($this->user, $this->edition, $this->now, $this->finance());
    }

    public function publicSiteUrl(): string
    {
        return route('home');
    }
}
