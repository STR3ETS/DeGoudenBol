<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Models\VoucherShortfall;
use App\Models\User;

/**
 * Tekort vastleggen: Bonbeheer vult aan en factureert door (facturatie via Commercie, buiten dit domein).
 */
final class RecordShortfall
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(VoucherCampaign $campaign, int $count, ?string $note, ?User $by): VoucherShortfall
    {
        $shortfall = $campaign->shortfalls()->create([
            'count' => max(1, $count),
            'note' => $note,
            'created_by' => $by?->getKey(),
        ]);

        $this->audit->record('voucher_campaign.shortfall', $campaign, ['count' => $shortfall->count], $by);

        return $shortfall;
    }
}
