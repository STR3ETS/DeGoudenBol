<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Commerce\Enums\PlacementStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Sponsorplaatsing: eigen record met locatie, periode en exclusiviteit (docs/04 §9).
 *
 * @property PlacementStatus $status
 */
#[Fillable(['edition_id', 'sponsor_id', 'product_id', 'location', 'province_id', 'entry_id', 'label', 'starts_at', 'ends_at', 'is_exclusive', 'status', 'price_cents', 'order_line_id'])]
class Placement extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'is_exclusive' => 'boolean',
            'status' => PlacementStatus::class,
            'price_cents' => 'integer',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }

    public function link(): HasOne
    {
        return $this->hasOne(SponsorLink::class);
    }

    /**
     * Zichtbaar op de site: actief en binnen de periode.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', PlacementStatus::Active)->where('starts_at', '<=', now())->where('ends_at', '>=', now());
    }

    /**
     * @param  Builder<self>  $query
     * @param  list<string>  $locations
     * @return Builder<self>
     */
    public function scopeAtLocations(Builder $query, array $locations): Builder
    {
        return $query->whereIn('location', $locations);
    }

    public function isLive(): bool
    {
        return $this->status === PlacementStatus::Active && $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    public function locationLabel(): string
    {
        return match (true) {
            $this->location === 'homepage' => 'Homepage',
            $this->location === 'national_top' => 'Landelijke toppositie',
            $this->location === 'final' => 'Finale',
            $this->location === 'national' => 'Landelijk partner',
            $this->location === 'charities' => 'Goede doelen',
            str_starts_with($this->location, 'province:') => 'Provinciepartner '.($this->province?->name ?? ''),
            str_starts_with($this->location, 'entry:') => 'Bij '.($this->entry?->public_name ?? 'deelnemer'),
            default => $this->location,
        };
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_cents);
    }
}
