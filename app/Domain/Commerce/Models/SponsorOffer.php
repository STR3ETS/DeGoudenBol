<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Commerce\Enums\OfferAudience;
use App\Domain\Commerce\Enums\OfferStatus;
use App\Domain\Commerce\Enums\OfferType;
use App\Domain\Edition\Models\Edition;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sponsoractie: voucher, proefpakket, cadeau, adviesgesprek of prijs voor winnaars. Pas zichtbaar na
 * redactionele goedkeuring. Staat los van de beoordeling; het panel ziet dit nooit.
 *
 * @property OfferType $type
 * @property OfferAudience $audience
 * @property OfferStatus $status
 */
#[Fillable(['edition_id', 'sponsor_id', 'type', 'audience', 'title', 'description', 'status', 'reviewed_by', 'reviewed_at', 'review_note'])]
class SponsorOffer extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OfferType::class,
            'audience' => OfferAudience::class,
            'status' => OfferStatus::class,
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Goedgekeurde acties van actieve sponsoren.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', OfferStatus::Approved)->whereHas('sponsor', fn (Builder $sponsor) => $sponsor->active());
    }
}
