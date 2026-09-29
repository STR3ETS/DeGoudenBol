<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Edition\Models\Edition;
use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deelnamepakket. Nooit een rangnummer in de naam; badges hangen niet aan een pakket.
 * Bij model B is er één basispakket; bij model A meerdere met voorraad per provincie.
 */
#[Fillable([
    'edition_id',
    'code',
    'name',
    'description',
    'price_cents',
    'vat_rate',
    'stock_per_province',
    'is_base',
    'is_active',
    'sort',
    'entitlements',
])]
class Package extends DomainModel
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'vat_rate' => 'float',
            'stock_per_province' => 'integer',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
            'sort' => 'integer',
            'entitlements' => 'array',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort');
    }

    public function vatCents(): int
    {
        return Money::vat($this->price_cents, $this->vat_rate);
    }

    public function priceInclVatCents(): int
    {
        return $this->price_cents + $this->vatCents();
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_cents);
    }

    public function formattedPriceInclVat(): string
    {
        return Money::format($this->priceInclVatCents());
    }
}
