<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Commerce\Enums\InvoiceStatus;
use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Factuur bij een order. Nummering is doorlopend per jaar en wordt in een
 * transactie met vergrendeling uitgegeven (InvoiceNumberGenerator).
 */
#[Fillable([
    'order_id',
    'year',
    'sequence',
    'number',
    'status',
    'issued_at',
    'due_at',
    'paid_at',
    'subtotal_cents',
    'vat_cents',
    'total_cents',
    'billing_name',
    'billing_address',
    'external_id',
    'pdf_path',
])]
class Invoice extends DomainModel
{
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'sequence' => 'integer',
            'status' => InvoiceStatus::class,
            'issued_at' => 'immutable_date',
            'due_at' => 'immutable_date',
            'paid_at' => 'immutable_datetime',
            'subtotal_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
            'billing_address' => 'array',
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

    public function formattedTotal(): string
    {
        return Money::format($this->total_cents);
    }
}
