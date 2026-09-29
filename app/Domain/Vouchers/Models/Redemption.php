<?php

namespace App\Domain\Vouchers\Models;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Vouchers\Enums\RedemptionMethod;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Verzilverpoging: verzilverd of geweigerd (met reden). Append-only.
 *
 * @property RedemptionMethod $method
 */
#[Fillable(['voucher_id', 'voucher_campaign_id', 'participant_user_id', 'result', 'method', 'refusal_reason', 'photo_path'])]
class Redemption extends DomainModel
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => RedemptionMethod::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $redemption): void {
            $redemption->created_at ??= now();
        });
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VoucherCampaign::class, 'voucher_campaign_id');
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(ParticipantUser::class, 'participant_user_id');
    }

    public function wasRedeemed(): bool
    {
        return $this->result === 'redeemed';
    }

    public function refusalLabel(): ?string
    {
        return match ($this->refusal_reason) {
            null => null,
            'unknown' => 'onbekende bon',
            'wrong_company' => 'bon van andere ondernemer',
            'already_redeemed' => 'al gebruikt',
            'expired' => 'verlopen',
            'void' => 'ongeldig gemaakt',
            default => $this->refusal_reason,
        };
    }
}
