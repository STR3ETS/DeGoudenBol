<?php

namespace App\Domain\Participants\Actions;

use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Notifications\DiscountReviewedNotification;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use App\Support\Mail\SendOnce;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Een administrator keurt het bewijs van de kortingsactie goed of af (briefing 29 okt §3).
 * Goedgekeurd: de order krijgt een kortingsregel en de bakker kan de actieprijs betalen.
 * Afgewezen: de bakker kan opnieuw bewijs aanleveren of de standaardprijs betalen.
 * In beide gevallen blijft de plek de ingestelde termijn vastgehouden.
 */
final class ReviewDiscount
{
    public function __construct(
        private readonly ApplyRate $applyRate,
        private readonly AuditLogger $audit,
        private readonly SendOnce $mail,
    ) {}

    public function __invoke(Entry $entry, bool $approve, User $reviewer, ?string $note = null): Entry
    {
        if (! $entry->isPendingPayment() || ! $entry->wantsDiscount()) {
            throw new InvalidArgumentException('Deze aanmelding heeft geen open kortingsaanvraag.');
        }

        if (! $approve && blank($note)) {
            throw new InvalidArgumentException('Geef de bakker een reden waarom de korting niet is goedgekeurd.');
        }

        $entry = DB::transaction(function () use ($entry, $approve, $reviewer, $note): Entry {
            $entry->forceFill([
                'discount_status' => $approve ? DiscountStatus::Approved : DiscountStatus::Rejected,
                'discount_reviewed_at' => now(),
                'discount_reviewed_by' => $reviewer->getKey(),
                'discount_note' => $note,
                'reservation_expires_at' => now()->addDays($entry->edition->settings->discountHoldDays),
            ])->save();

            ($this->applyRate)($entry, $approve ? EntryRate::Discount : EntryRate::Standard, keepRequest: ! $approve);

            $this->audit->record($approve ? 'entry.discount_approved' : 'entry.discount_rejected', $entry, ['note' => $note]);

            return $entry->refresh();
        });

        // Per beslissing één mail; een nieuwe beslissing (na opnieuw aanleveren) mag weer mailen.
        $round = ($approve ? 'approved' : 'rejected').':'.$entry->discount_reviewed_at->format('YmdHisu').':'.$entry->discount_submitted_at?->getTimestamp();

        foreach ($entry->company->owners() as $owner) {
            $this->mail->send($owner, new DiscountReviewedNotification($entry, $approve), "entry:{$entry->getKey()}:discount:{$round}");
        }

        return $entry;
    }
}
