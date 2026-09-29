<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Edition\Models\Edition;
use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Bestelling van een deelnemer of sponsor. Omschrijvingen noemen nooit een rangpositie.
 *
 * @property OrderStatus $status
 */
#[Fillable([
    'edition_id',
    'orderable_type',
    'orderable_id',
    'company_id',
    'participant_user_id',
    'status',
    'subtotal_cents',
    'vat_cents',
    'total_cents',
    'currency',
    'expires_at',
    'paid_at',
    'cancelled_at',
    'metadata',
])]
class Order extends DomainModel
{
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
            'expires_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'metadata' => 'array',
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

    protected static function booted(): void
    {
        static::created(function (self $order): void {
            if (blank($order->number)) {
                $order->forceFill(['number' => sprintf('DGB%02d-%06d', $order->created_at->year % 100, $order->getKey())])->saveQuietly();
            }
        });
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class)->orderBy('sort');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total_cents);
    }

    public function recalculateTotals(): void
    {
        $lines = $this->lines()->get();

        $this->forceFill([
            'subtotal_cents' => (int) $lines->sum('subtotal_cents'),
            'vat_cents' => (int) $lines->sum('vat_cents'),
            'total_cents' => (int) $lines->sum('total_cents'),
        ])->save();
    }
}
