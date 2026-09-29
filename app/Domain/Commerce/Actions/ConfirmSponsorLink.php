<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Platform\Services\AuditLogger;

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

        return $link;
    }
}
