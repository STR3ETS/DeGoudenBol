<?php

namespace App\Domain\Vouchers\Models;

use App\Domain\Edition\Models\TermsVersion;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Winnaar die door de ondernemer is ingevoerd. Bevestigt zelf via de claimlink; gegevens minimaal
 * en per 31 maart geanonimiseerd.
 */
#[Fillable(['voucher_campaign_id', 'first_name', 'last_initial', 'email', 'claim_token_hash', 'claimed_at', 'terms_version_id', 'consent_public_name', 'consent_photo', 'anonymized_at'])]
#[Hidden(['claim_token_hash'])]
class VoucherWinner extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claimed_at' => 'immutable_datetime',
            'consent_public_name' => 'boolean',
            'consent_photo' => 'boolean',
            'anonymized_at' => 'immutable_datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VoucherCampaign::class, 'voucher_campaign_id');
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    public function terms(): BelongsTo
    {
        return $this->belongsTo(TermsVersion::class, 'terms_version_id');
    }

    public function hasClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    /**
     * "Voornaam L." – alleen tonen met toestemming.
     */
    public function displayName(): string
    {
        return trim($this->first_name.' '.rtrim($this->last_initial, '.').'.');
    }
}
