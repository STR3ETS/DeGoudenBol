<?php

namespace App\Domain\Vouchers\Models;

use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Digitale cadeaubon: leesbaar nummer plus een los, onraadbaar QR-token (alleen gehasht opgeslagen).
 *
 * @property VoucherStatus $status
 */
#[Fillable(['code', 'qr_token_hash', 'voucher_campaign_id', 'voucher_winner_id', 'value_cents', 'status', 'issued_at', 'expires_at', 'redeemed_at'])]
#[Hidden(['qr_token_hash'])]
class Voucher extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_cents' => 'integer',
            'status' => VoucherStatus::class,
            'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'redeemed_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VoucherCampaign::class, 'voucher_campaign_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(VoucherWinner::class, 'voucher_winner_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function isRedeemable(): bool
    {
        return $this->status === VoucherStatus::Issued && $this->expires_at->isFuture();
    }

    /**
     * Status zoals de controlepagina hem toont: een verlopen bon die nog op "geldig" staat telt als verlopen.
     */
    public function effectiveStatus(): VoucherStatus
    {
        if ($this->status === VoucherStatus::Issued && $this->expires_at->isPast()) {
            return VoucherStatus::Expired;
        }

        return $this->status;
    }

    public function formattedValue(): string
    {
        return Money::format($this->value_cents);
    }
}
