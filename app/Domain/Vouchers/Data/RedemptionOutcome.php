<?php

namespace App\Domain\Vouchers\Data;

use App\Domain\Vouchers\Models\Redemption;
use App\Domain\Vouchers\Models\Voucher;

/**
 * Resultaat van een verzilverpoging: groot, eenduidig en in statuskleur (docs/02 §7).
 */
final readonly class RedemptionOutcome
{
    public function __construct(
        public string $result,
        public string $title,
        public string $text,
        public string $chip,
        public ?Voucher $voucher,
        public ?Redemption $redemption,
    ) {}

    public function isRedeemed(): bool
    {
        return $this->result === 'redeemed';
    }
}
