<?php

namespace App\Domain\Charities\Actions;

use App\Domain\Charities\Enums\CharityStatus;
use App\Domain\Charities\Exceptions\CharityException;
use App\Domain\Charities\Models\Charity;
use App\Domain\Charities\Models\CharityPayout;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Uitbetaling vastleggen (Financiën); het doel gaat naar "uitbetaald".
 */
final class RecordPayout
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(Charity $charity, int $amountCents, CarbonInterface $paidAt, ?string $reference = null, ?string $note = null, ?User $by = null): CharityPayout
    {
        if (! $charity->status->receivesReservations()) {
            throw new CharityException('Alleen een goedgekeurd of gekoppeld doel kan worden uitbetaald.');
        }

        if ($amountCents <= 0) {
            throw new CharityException('Het bedrag moet groter zijn dan nul.');
        }

        return DB::transaction(function () use ($charity, $amountCents, $paidAt, $reference, $note, $by): CharityPayout {
            $payout = $charity->payouts()->create([
                'edition_id' => $charity->edition_id,
                'amount_cents' => $amountCents,
                'paid_at' => $paidAt->toDateString(),
                'reference' => $reference,
                'note' => $note,
                'created_by' => $by?->getKey(),
            ]);

            $charity->forceFill(['status' => CharityStatus::PaidOut])->save();
            $this->audit->record('charity.paid_out', $charity, ['amount_cents' => $amountCents], $by);

            return $payout;
        });
    }
}
