<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Vouchers\Exceptions\VoucherException;
use App\Domain\Vouchers\Models\VoucherWinner;
use App\Domain\Vouchers\Notifications\VoucherClaimNotification;
use App\Domain\Vouchers\Services\VoucherCode;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Nieuwe claimlink (oude vervalt) voor een winnaar die de mail kwijt is.
 */
final class ResendClaimLink
{
    public function __construct(
        private readonly VoucherCode $codes,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(VoucherWinner $winner, ?User $by = null): void
    {
        if ($winner->hasClaimed()) {
            throw new VoucherException('Deze winnaar heeft de bon al geclaimd.');
        }

        $token = $this->codes->token();
        $winner->forceFill(['claim_token_hash' => $token['hash']])->save();

        $this->audit->record('voucher_winner.claim_link_resent', $winner->campaign, ['winner' => $winner->getKey()], $by);

        Notification::route('mail', $winner->email)->notify(new VoucherClaimNotification($winner, $token['plain']));
    }
}
