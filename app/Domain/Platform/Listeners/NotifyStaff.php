<?php

namespace App\Domain\Platform\Listeners;

use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Participants\Events\ObjectionSubmitted;
use App\Domain\Participants\Events\ProfileSubmitted;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\StaffNotifier;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Events\BatchApproved;
use App\Domain\Ranking\Events\BatchPublished;
use App\Domain\Ranking\Events\BatchSubmitted;
use App\Domain\Ranking\Events\CorrectionCaseOpened;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Filament\Resources\CorrectionCases\CorrectionCaseResource;
use App\Filament\Resources\Objections\ObjectionResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Profiles\ProfileResource;
use App\Filament\Resources\PublicationBatches\PublicationBatchResource;
use App\Support\DutchTime;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * Eén luisteraar voor alle momenten waarop een medewerker een melding in de bel krijgt.
 * Wie wat krijgt volgt de rollen uit docs/01; de veroorzaker krijgt zijn eigen melding niet.
 */
final class NotifyStaff
{
    public function __construct(private readonly StaffNotifier $notifier) {}

    public function onBatchSubmitted(BatchSubmitted $event): void
    {
        $batch = $event->batch;
        $items = $batch->items()->count();

        $this->notifier->notify(
            [StaffRole::Publisher],
            "{$batch->label()} wacht op goedkeuring",
            "{$items} ".($items === 1 ? 'uitslag' : 'uitslagen').", ingediend door {$event->submitter->name}. Twee goedkeuringen nodig; de indiener keurt niet zelf.",
            $this->batchUrl($batch),
            $event->submitter,
            'heroicon-o-megaphone',
            'warning',
        );
    }

    public function onBatchApproved(BatchApproved $event): void
    {
        $batch = $event->batch;

        if ($batch->status === BatchStatus::Approved) {
            $this->notifier->notify(
                [StaffRole::Publisher, StaffRole::Communication],
                "{$batch->label()} is goedgekeurd",
                'Publiceert automatisch op '.DutchTime::format($batch->scheduled_at, 'dddd D MMMM [om] HH:mm').'.',
                $this->batchUrl($batch),
                null,
                'heroicon-o-check-badge',
                'success',
            );

            return;
        }

        $remaining = PublicationBatch::REQUIRED_APPROVALS - $event->approvalsCount;

        $this->notifier->notify(
            [StaffRole::Publisher],
            "Nog {$remaining} ".($remaining === 1 ? 'goedkeuring' : 'goedkeuringen')." nodig voor {$batch->label()}",
            "{$event->approver->name} heeft goedgekeurd.",
            $this->batchUrl($batch),
            $event->approver,
            'heroicon-o-check-badge',
            'warning',
        );
    }

    public function onBatchPublished(BatchPublished $event): void
    {
        $batch = $event->batch;
        $items = $batch->items()->with('province')->get();
        $provinces = $items->map(fn ($item) => $item->province?->name)->filter()->unique()->sort()->implode(', ');

        $this->notifier->notify(
            [StaffRole::Communication, StaffRole::Publisher],
            'Uitslagen gepubliceerd: '.$items->count().' '.($items->count() === 1 ? 'uitslag' : 'uitslagen'),
            ($provinces !== '' ? "Provincies: {$provinces}. " : '').'Badges en socialkits staan klaar voor de deelnemers.',
            $this->batchUrl($batch),
            null,
            'heroicon-o-megaphone',
            'success',
        );
    }

    public function onObjectionSubmitted(ObjectionSubmitted $event): void
    {
        $objection = $event->objection;
        $name = $objection->entry?->public_name ?? 'een deelnemer';

        $this->notifier->notify(
            [StaffRole::Publisher],
            "Nieuw bezwaar van {$name}",
            Str::limit((string) $objection->reason, 140).' Reageer binnen drie werkdagen.',
            ObjectionResource::getUrl('index'),
            null,
            'heroicon-o-scale',
            'warning',
        );
    }

    public function onCorrectionCaseOpened(CorrectionCaseOpened $event): void
    {
        $name = $event->case->entry?->public_name ?? 'een deelnemer';

        $this->notifier->notify(
            [StaffRole::Publisher],
            "Correctiedossier geopend voor {$name}",
            "Geopend door {$event->submitter->name}: ".Str::limit((string) $event->case->reason, 120),
            CorrectionCaseResource::getUrl('index'),
            $event->submitter,
            'heroicon-o-document-magnifying-glass',
            'info',
        );
    }

    public function onProfileSubmitted(ProfileSubmitted $event): void
    {
        $company = $event->profile->company?->name ?? 'een bakker';

        $this->notifier->notify(
            [StaffRole::Communication],
            "Profieltekst ingediend door {$company}",
            'Keur de tekst voordat hij op de site komt.',
            ProfileResource::getUrl('index'),
            null,
            'heroicon-o-document-check',
            'warning',
        );
    }

    public function onOrderPaid(OrderPaid $event): void
    {
        $order = $event->order;
        $orderable = $order->orderable;
        $name = $orderable?->public_name ?? $orderable?->name ?? 'order';

        $this->notifier->notify(
            [StaffRole::Finance],
            "Betaling ontvangen: {$name}",
            Money::format((int) $order->total_cents).' incl. btw.',
            OrderResource::getUrl('index'),
            null,
            'heroicon-o-banknotes',
            'success',
        );
    }

    private function batchUrl(PublicationBatch $batch): string
    {
        return PublicationBatchResource::getUrl('view', ['record' => $batch]);
    }
}
