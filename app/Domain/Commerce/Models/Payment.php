<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Commerce\Enums\PaymentStatus;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Betaalpoging bij een order via een betaalprovider (Mollie, of lokaal 'fake').
 *
 * @property PaymentStatus $status
 */
#[Fillable([
    'order_id',
    'provider',
    'provider_id',
    'status',
    'amount_cents',
    'method',
    'checkout_url',
    'paid_at',
    'raw',
])]
class Payment extends DomainModel
{
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'paid_at' => 'immutable_datetime',
            'raw' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }
}
