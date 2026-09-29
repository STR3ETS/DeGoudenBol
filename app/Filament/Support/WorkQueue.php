<?php

namespace App\Filament\Support;

use App\Domain\Commerce\Enums\InvoiceStatus;
use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Models\Invoice;
use App\Domain\Commerce\Models\Order;
use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Enums\ModerationStatus;
use App\Domain\Participants\Enums\ObjectionStatus;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\Objection;
use App\Domain\Participants\Models\Profile;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Enums\CorrectionCaseStatus;
use App\Domain\Ranking\Enums\FinalistStatus;
use App\Domain\Ranking\Models\CorrectionCase;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Testing\Enums\CorrectionStatus;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\ScoreCorrection;
use App\Domain\Testing\Models\TestSession;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Filament\Pages\Intake;
use App\Filament\Resources\Charities\CharityResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CorrectionCases\CorrectionCaseResource;
use App\Filament\Resources\Entries\EntryResource;
use App\Filament\Resources\Finalists\FinalistResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Objections\ObjectionResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Profiles\ProfileResource;
use App\Filament\Resources\PublicationBatches\PublicationBatchResource;
use App\Filament\Resources\Samples\SampleResource;
use App\Filament\Resources\TestSessions\TestSessionResource;
use App\Filament\Resources\VoucherCampaigns\VoucherCampaignResource;
use App\Models\User;
use App\Support\DutchTime;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Jouw werk vandaag": per rol de dingen die nu aandacht vragen, met aantal en directe actie.
 * Zichtbaarheid volgt de resources (canViewAny), dus de beheerder ziet alles.
 *
 * @phpstan-type WorkItem array{title: string, text: string, count: int|null, url: string|null, action: string, tone: string, icon: string}
 */
final class WorkQueue
{
    /** @var list<WorkItem>|null */
    private ?array $items = null;

    /**
     * @param  array<string, mixed>|null  $finance
     */
    public function __construct(
        private readonly User $user,
        private readonly ?Edition $edition,
        private readonly CarbonImmutable $now,
        private readonly ?array $finance = null,
    ) {}

    /**
     * @return list<WorkItem>
     */
    public function items(): array
    {
        return $this->items ??= $this->build();
    }

    public function count(): int
    {
        return count($this->items());
    }

    /**
     * @return list<WorkItem>
     */
    private function build(): array
    {
        if ($this->edition === null) {
            return [];
        }

        return [
            ...$this->deliveriesToday(),
            ...$this->sessionsToday(),
            ...$this->freshness(),
            ...$this->scoreReview(),
            ...$this->scoreCorrections(),
            ...$this->publication(),
            ...$this->objections(),
            ...$this->correctionCases(),
            ...$this->finalists(),
            ...$this->profiles(),
            ...$this->voucherWinners(),
            ...$this->openOrders(),
            ...$this->overdueInvoices(),
            ...$this->charityPayouts(),
            ...$this->missingCoordinates(),
        ];
    }

    /**
     * @return list<WorkItem>
     */
    private function deliveriesToday(): array
    {
        if (! Intake::canAccess()) {
            return [];
        }

        $slots = DeliverySlot::query()
            ->where('edition_id', $this->edition->getKey())
            ->whereBetween('starts_at', [$this->now->startOfDay()->utc(), $this->now->endOfDay()->utc()])
            ->withCount(['entries as expected_count' => fn (Builder $query) => $query->where('status', EntryStatus::Scheduled)])
            ->orderBy('starts_at')
            ->get();

        if ($slots->isEmpty()) {
            return [];
        }

        $expected = (int) $slots->sum('expected_count');

        return [[
            'title' => 'Leveringen vandaag',
            'text' => ($expected === 0 ? 'Alles is ontvangen' : "{$expected} nog te ontvangen").' · '.$slots->count().' '.($slots->count() === 1 ? 'slot' : 'slots').' van '.DutchTime::format($slots->first()->starts_at, 'HH:mm').' tot '.DutchTime::format($slots->last()->ends_at, 'HH:mm'),
            'count' => $expected,
            'url' => Intake::getUrl(),
            'action' => 'Ontvangen',
            'tone' => $expected > 0 ? 'goud' : 'succes',
            'icon' => 'heroicon-o-qr-code',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function sessionsToday(): array
    {
        if (! TestSessionResource::canViewAny()) {
            return [];
        }

        return TestSession::query()
            ->where('edition_id', $this->edition->getKey())
            ->whereBetween('starts_at', [$this->now->startOfDay()->utc(), $this->now->endOfDay()->utc()])
            ->whereIn('status', [SessionStatus::Planned, SessionStatus::Running])
            ->withCount(['samples', 'panelists'])
            ->orderBy('starts_at')
            ->get()
            ->map(function (TestSession $session): array {
                $hasSchedule = $session->schedule_generated_at !== null;
                $isRunning = $session->status === SessionStatus::Running;

                return [
                    'title' => $session->displayName().' · '.DutchTime::format($session->starts_at, 'HH:mm'),
                    'text' => "{$session->samples_count} monsters · {$session->panelists_count} panelleden · ".($hasSchedule ? ($isRunning ? 'loopt' : 'schema staat klaar') : 'schema nog niet gegenereerd'),
                    'count' => (int) $session->samples_count,
                    'url' => TestSessionResource::getUrl('view', ['record' => $session]),
                    'action' => $hasSchedule ? ($isRunning ? 'Bekijken' : 'Starten') : 'Schema genereren',
                    'tone' => $hasSchedule ? ($isRunning ? 'succes' : 'info') : 'waarschuwing',
                    'icon' => 'heroicon-o-beaker',
                ];
            })
            ->all();
    }

    /**
     * @return list<WorkItem>
     */
    private function freshness(): array
    {
        if (! SampleResource::canViewAny()) {
            return [];
        }

        $expiring = Sample::query()
            ->where('edition_id', $this->edition->getKey())
            ->whereIn('status', [SampleStatus::Received, SampleStatus::Numbered, SampleStatus::Scheduled])
            ->whereHas('intake', fn (Builder $query) => $query->whereBetween('freshness_expires_at', [$this->now->utc(), $this->now->addMinutes(90)->utc()]))
            ->count();

        if ($expiring === 0) {
            return [];
        }

        return [[
            'title' => 'Versheid loopt af',
            'text' => "{$expiring} ".($expiring === 1 ? 'monster verloopt' : 'monsters verlopen').' binnen anderhalf uur en '.($expiring === 1 ? 'is' : 'zijn').' nog niet beoordeeld.',
            'count' => $expiring,
            'url' => SampleResource::getUrl('index'),
            'action' => 'Bekijken',
            'tone' => 'fout',
            'icon' => 'heroicon-o-clock',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function scoreReview(): array
    {
        if (! SampleResource::canViewAny()) {
            return [];
        }

        $count = Sample::query()
            ->where('edition_id', $this->edition->getKey())
            ->whereHas('result', fn (Builder $query) => $query->where('status', ResultStatus::Pending))
            ->has('scorecards')
            ->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Scorecontrole',
            'text' => "{$count} ".($count === 1 ? 'monster wacht' : 'monsters wachten').' op controle van gemiddelden en afwijkers.',
            'count' => $count,
            'url' => SampleResource::getUrl('index', ['filters' => ['needs_attention' => ['isActive' => true]]]),
            'action' => 'Controleren',
            'tone' => 'waarschuwing',
            'icon' => 'heroicon-o-clipboard-document-check',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function scoreCorrections(): array
    {
        if (! SampleResource::canViewAny()) {
            return [];
        }

        $count = ScoreCorrection::query()
            ->where('status', CorrectionStatus::Pending)
            ->where('requested_by', '!=', $this->user->getKey())
            ->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Scorecorrecties',
            'text' => "{$count} ".($count === 1 ? 'correctie wacht' : 'correcties wachten').' op een tweede paar ogen.',
            'count' => $count,
            'url' => SampleResource::getUrl('index'),
            'action' => 'Beoordelen',
            'tone' => 'info',
            'icon' => 'heroicon-o-pencil-square',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function publication(): array
    {
        if (! PublicationBatchResource::canViewAny()) {
            return [];
        }

        $batch = PublicationBatch::query()
            ->where('edition_id', $this->edition->getKey())
            ->whereIn('status', [BatchStatus::Draft, BatchStatus::PendingApproval, BatchStatus::Approved])
            ->withCount(['items', 'approvals'])
            ->orderBy('scheduled_at')
            ->first();

        if ($batch === null) {
            return [];
        }

        $mine = $batch->submitted_by === $this->user->getKey()
            || $batch->approvals()->where('user_id', $this->user->getKey())->exists();

        [$action, $tone] = match ($batch->status) {
            BatchStatus::Draft => ['Indienen', 'info'],
            BatchStatus::PendingApproval => [$mine ? 'Wacht op collega' : 'Goedkeuren', 'waarschuwing'],
            default => ['Publiceren', 'succes'],
        };

        return [[
            'title' => 'Publicatie '.DutchTime::format($batch->scheduled_at, 'dddd D MMMM [om] HH:mm'),
            'text' => "{$batch->items_count} items · {$batch->approvals_count} van 2 goedkeuringen · ".$batch->status->getLabel(),
            'count' => (int) $batch->items_count,
            'url' => PublicationBatchResource::getUrl('view', ['record' => $batch]),
            'action' => $action,
            'tone' => $tone,
            'icon' => 'heroicon-o-megaphone',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function objections(): array
    {
        if (! ObjectionResource::canViewAny()) {
            return [];
        }

        $count = Objection::query()->whereIn('status', [ObjectionStatus::Submitted, ObjectionStatus::Reviewing])->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Bezwaren',
            'text' => "{$count} ".($count === 1 ? 'bezwaar is' : 'bezwaren zijn').' nog niet afgehandeld; reageer binnen drie werkdagen.',
            'count' => $count,
            'url' => ObjectionResource::getUrl('index'),
            'action' => 'Beoordelen',
            'tone' => 'waarschuwing',
            'icon' => 'heroicon-o-scale',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function correctionCases(): array
    {
        if (! CorrectionCaseResource::canViewAny()) {
            return [];
        }

        $count = CorrectionCase::query()
            ->where('status', CorrectionCaseStatus::PendingApproval)
            ->where('submitted_by', '!=', $this->user->getKey())
            ->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Correcties na publicatie',
            'text' => "{$count} ".($count === 1 ? 'dossier wacht' : 'dossiers wachten').' op jouw goedkeuring.',
            'count' => $count,
            'url' => CorrectionCaseResource::getUrl('index'),
            'action' => 'Goedkeuren',
            'tone' => 'info',
            'icon' => 'heroicon-o-document-magnifying-glass',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function finalists(): array
    {
        if (! FinalistResource::canViewAny()) {
            return [];
        }

        $count = Finalist::query()->where('edition_id', $this->edition->getKey())->where('status', FinalistStatus::Invited)->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Finale',
            'text' => "{$count} ".($count === 1 ? 'finalist heeft' : 'finalisten hebben').' de uitnodiging nog niet beantwoord.',
            'count' => $count,
            'url' => FinalistResource::getUrl('index'),
            'action' => 'Bekijken',
            'tone' => 'neutraal',
            'icon' => 'heroicon-o-trophy',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function profiles(): array
    {
        if (! ProfileResource::canViewAny()) {
            return [];
        }

        $count = Profile::query()->where('moderation_status', ModerationStatus::Pending)->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Profielteksten keuren',
            'text' => "{$count} ".($count === 1 ? 'tekst is' : 'teksten zijn').' ingediend door bakkers en nog niet op de site.',
            'count' => $count,
            'url' => ProfileResource::getUrl('index'),
            'action' => 'Keuren',
            'tone' => 'waarschuwing',
            'icon' => 'heroicon-o-document-check',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function voucherWinners(): array
    {
        if (! VoucherCampaignResource::canViewAny()) {
            return [];
        }

        $campaigns = VoucherCampaign::query()
            ->where('edition_id', $this->edition->getKey())
            ->where('status', CampaignStatus::Open)
            ->withCount('winners')
            ->orderBy('winners_deadline_at')
            ->get()
            ->filter(fn (VoucherCampaign $campaign) => $campaign->winners_count < $campaign->winner_count);

        if ($campaigns->isEmpty()) {
            return [];
        }

        $first = $campaigns->first();

        return [[
            'title' => 'Winnaars invoeren',
            'text' => $campaigns->count().' '.($campaigns->count() === 1 ? 'actie wacht' : 'acties wachten').' op winnaars · uiterlijk '.DutchTime::format($first->winners_deadline_at, 'dddd D MMMM [om] HH:mm'),
            'count' => $campaigns->count(),
            'url' => $campaigns->count() === 1 ? VoucherCampaignResource::getUrl('view', ['record' => $first]) : VoucherCampaignResource::getUrl('index'),
            'action' => 'Invoeren',
            'tone' => 'goud',
            'icon' => 'heroicon-o-gift',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function openOrders(): array
    {
        if (! OrderResource::canViewAny()) {
            return [];
        }

        $count = Order::query()
            ->where('edition_id', $this->edition->getKey())
            ->where('status', OrderStatus::Pending)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Openstaande orders',
            'text' => "{$count} ".($count === 1 ? 'order wacht' : 'orders wachten').' op betaling; bankoverschrijvingen handmatig afboeken.',
            'count' => $count,
            'url' => OrderResource::getUrl('index', ['filters' => ['status' => ['value' => OrderStatus::Pending->value]]]),
            'action' => 'Afboeken',
            'tone' => 'info',
            'icon' => 'heroicon-o-shopping-bag',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function overdueInvoices(): array
    {
        if (! InvoiceResource::canViewAny()) {
            return [];
        }

        $count = Invoice::query()
            ->whereHas('order', fn (Builder $query) => $query->where('edition_id', $this->edition->getKey()))
            ->where(fn (Builder $query) => $query
                ->where('status', InvoiceStatus::Overdue)
                ->orWhere(fn (Builder $open) => $open->where('status', InvoiceStatus::Open)->where('due_at', '<', now())))
            ->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Facturen over de vervaldatum',
            'text' => "{$count} ".($count === 1 ? 'factuur is' : 'facturen zijn').' nog niet betaald.',
            'count' => $count,
            'url' => InvoiceResource::getUrl('index'),
            'action' => 'Herinneren',
            'tone' => 'fout',
            'icon' => 'heroicon-o-document-currency-euro',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function charityPayouts(): array
    {
        $open = (int) ($this->finance['charity_open_cents'] ?? 0);

        if ($open <= 0 || ! CharityResource::canViewAny()) {
            return [];
        }

        return [[
            'title' => 'Goede doelen uitbetalen',
            'text' => Money::format($open).' gereserveerd en nog niet uitbetaald.',
            'count' => null,
            'url' => CharityResource::getUrl('index'),
            'action' => 'Uitbetalen',
            'tone' => 'goud',
            'icon' => 'heroicon-o-heart',
        ]];
    }

    /**
     * @return list<WorkItem>
     */
    private function missingCoordinates(): array
    {
        if (! EntryResource::canViewAny()) {
            return [];
        }

        $count = Entry::query()
            ->where('edition_id', $this->edition->getKey())
            ->confirmed()
            ->whereHas('company.primaryLocation', fn (Builder $query) => $query->whereNull('lat'))
            ->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Adressen zonder coördinaten',
            'text' => 'Nodig voor de kaart en de provinciebepaling.',
            'count' => $count,
            'url' => CompanyResource::canViewAny() ? CompanyResource::getUrl('index') : EntryResource::getUrl('index'),
            'action' => 'Aanvullen',
            'tone' => 'neutraal',
            'icon' => 'heroicon-o-map-pin',
        ]];
    }
}
