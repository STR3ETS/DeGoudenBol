<?php

namespace App\Domain\Commerce\Models;

use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Factuurregel. 'counts_for_charity' bepaalt of de regel meetelt voor de 10%-reservering.
 */
#[Fillable([
    'order_id',
    'lineable_type',
    'lineable_id',
    'description',
    'quantity',
    'unit_price_cents',
    'vat_rate',
    'subtotal_cents',
    'vat_cents',
    'total_cents',
    'counts_for_charity',
    'sort',
])]
class OrderLine extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'vat_rate' => 'float',
            'subtotal_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
            'counts_for_charity' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lineable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Berekent subtotaal, btw en totaal uit aantal, stukprijs en tarief.
     */
    public static function amounts(int $quantity, int $unitPriceCents, float $vatRate): array
    {
        $subtotal = $quantity * $unitPriceCents;
        $vat = Money::vat($subtotal, $vatRate);

        return [
            'subtotal_cents' => $subtotal,
            'vat_cents' => $vat,
            'total_cents' => $subtotal + $vat,
        ];
    }
}
