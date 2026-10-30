<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\OfferStatus;
use App\Domain\Commerce\Models\SponsorOffer;
use App\Domain\Commerce\Notifications\SponsorMail;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use App\Support\Mail\SendOnce;
use InvalidArgumentException;

/**
 * Redactionele goedkeuring van een sponsoractie (briefing 29 okt §10). Pas daarna zichtbaar op de
 * sponsorpagina en in het portaal van de doelgroep.
 */
final class ReviewSponsorOffer
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SendOnce $mail,
    ) {}

    public function __invoke(SponsorOffer $offer, bool $approve, User $reviewer, ?string $note = null): SponsorOffer
    {
        if (! $approve && blank($note)) {
            throw new InvalidArgumentException('Geef de sponsor een toelichting bij de afwijzing.');
        }

        $offer->forceFill([
            'status' => $approve ? OfferStatus::Approved : OfferStatus::Rejected,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->audit->record($approve ? 'sponsor_offer.approved' : 'sponsor_offer.rejected', $offer, ['title' => $offer->title], $reviewer);

        $sponsor = $offer->sponsor;

        if (filled($sponsor?->contact_email)) {
            $this->mail->send($sponsor->contact_email, new SponsorMail(SponsorMail::OFFER_REVIEWED, $sponsor, offer: $offer), "offer:{$offer->getKey()}:".$offer->status->value);
        }

        return $offer;
    }
}
