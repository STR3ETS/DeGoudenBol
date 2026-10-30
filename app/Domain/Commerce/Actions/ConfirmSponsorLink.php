<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Commerce\Notifications\SponsorMail;
use App\Domain\Platform\Services\AuditLogger;
use App\Support\Mail\SendOnce;

/**
 * De deelnemer bevestigt of weigert "Bakt met [sponsor]".
 */
final class ConfirmSponsorLink
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(SponsorLink $link, bool $confirm): SponsorLink
    {
        $link->forceFill($confirm
            ? ['confirmed_by_company_at' => now(), 'declined_at' => null]
            : ['declined_at' => now(), 'confirmed_by_company_at' => null])->save();

        $this->audit->record($confirm ? 'sponsor_link.confirmed' : 'sponsor_link.declined', $link, ['company' => $link->company_id, 'sponsor' => $link->sponsor_id], null);

        // De sponsor hoort de uitkomst: goedgekeurd of afgewezen (briefing 29 okt §11).
        $sponsor = $link->sponsor;

        if (filled($sponsor?->contact_email)) {
            app(SendOnce::class)->send($sponsor->contact_email, new SponsorMail(SponsorMail::LINK_DECIDED, $sponsor, link: $link), "link:{$link->getKey()}:decided:".($confirm ? 'yes' : 'no'));
        }

        return $link;
    }
}
