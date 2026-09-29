<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Commerce\Enums\AvailabilityPhase;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Edition\Models\Edition;
use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product uit de sponsorcatalogus. Omschrijvingen noemen de inhoud, nooit een rangpositie.
 *
 * @property ProductCode $code
 * @property AvailabilityPhase $available_from_phase
 */
#[Fillable(['edition_id', 'code', 'name', 'description', 'price_cents', 'extra_link_price_cents', 'vat_rate', 'available_from_phase', 'is_custom', 'sort'])]
class Product extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => ProductCode::class,
            'price_cents' => 'integer',
            'extra_link_price_cents' => 'integer',
            'vat_rate' => 'float',
            'available_from_phase' => AvailabilityPhase::class,
            'is_custom' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_cents);
    }

    public function formattedExtraPrice(): ?string
    {
        return $this->extra_link_price_cents !== null ? Money::format($this->extra_link_price_cents) : null;
    }
}
