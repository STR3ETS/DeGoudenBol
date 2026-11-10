<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Betaalde zichtbaarheidsplek in een provincie: puur marketing, los van de test. Heeft bewust geen enkele
 * koppeling met het rankingdomein; de uitslag weet niet van deze plekken.
 */
#[Fillable(['edition_id', 'province_id', 'entry_id', 'company_id', 'position', 'price_cents', 'order_id', 'reserved_until', 'paid_at', 'starts_at', 'ends_at'])]
class VisibilitySpot extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'price_cents' => 'integer',
            'reserved_until' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Bezet: betaald, of nog gereserveerd tijdens een lopende betaling.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeTaken(Builder $query): Builder
    {
        return $query->where(fn (Builder $taken) => $taken->whereNotNull('paid_at')->orWhere('reserved_until', '>', now()));
    }

    /**
     * Zichtbaar op de site: betaald, binnen de periode, en de inschrijving doet nog mee. Zonder startmoment
     * geldt de plek direct vanaf de betaling.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereNotNull('paid_at')
            ->where(fn (Builder $started) => $started->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where('ends_at', '>', now())
            ->whereHas('entry', fn (Builder $entry) => $entry->confirmed());
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Betaald en binnen de periode waarin de vermelding op de site staat.
     */
    public function isShowing(): bool
    {
        return $this->isPaid() && ($this->starts_at === null || $this->starts_at->isPast()) && $this->ends_at->isFuture();
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_cents);
    }

    /**
     * Voor het orderoverzicht in de backoffice, dat de naam van de inschrijving toont.
     */
    public function getPublicNameAttribute(): ?string
    {
        return $this->entry?->public_name;
    }
}
