<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Domain\Vouchers\Exceptions\VoucherException;
use App\Domain\Vouchers\Models\Voucher;
use App\Models\User;

/**
 * Bon ongeldig maken (misbruik, fout ingevoerde winnaar). Alleen vanuit Bonbeheer, met reden in de audit.
 */
final class VoidVoucher
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(Voucher $voucher, string $reason, ?User $by = null): Voucher
    {
        $updated = Voucher::query()
            ->whereKey($voucher->getKey())
            ->where('status', VoucherStatus::Issued)
            ->update(['status' => VoucherStatus::Void->value, 'updated_at' => now()]);

        if ($updated !== 1) {
            throw new VoucherException('Alleen een geldige, nog niet verzilverde bon kan ongeldig worden gemaakt.');
        }

        $this->audit->record('voucher.voided', $voucher, ['reason' => $reason], $by);

        return $voucher->refresh();
    }
}
