<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\SponsorStatus;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Notifications\SponsorMail;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use App\Support\Mail\SendOnce;

/**
 * Gecontroleerd zichtbaar worden (briefing 29 okt, acceptatiecriteria): de organisatie keurt logo en
 * gegevens; pas daarna staat de sponsor op de site. De sponsor krijgt één keer bericht.
 */
final class ApproveSponsor
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SendOnce $mail,
    ) {}

    public function __invoke(Sponsor $sponsor, User $by): Sponsor
    {
        $sponsor->forceFill(['status' => SponsorStatus::Active, 'approved_at' => $sponsor->approved_at ?? now()])->save();

        $this->audit->record('sponsor.approved', $sponsor, ['name' => $sponsor->name], $by);

        if (filled($sponsor->contact_email)) {
            $this->mail->send($sponsor->contact_email, new SponsorMail(SponsorMail::PUBLISHED, $sponsor), "sponsor:{$sponsor->getKey()}:published");
        }

        return $sponsor;
    }
}
