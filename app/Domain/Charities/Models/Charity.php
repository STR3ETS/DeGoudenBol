<?php

namespace App\Domain\Charities\Models;

use App\Domain\Charities\Enums\CharityStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Goed doel, voorgedragen door een deelnemer (portaal) of sponsor (backoffice).
 *
 * @property CharityStatus $status
 */
#[Fillable(['edition_id', 'name', 'slug', 'kvk_or_rsin', 'is_anbi', 'province_id', 'category', 'motivation', 'website', 'status', 'review_checklist', 'review_note', 'nominated_by_type', 'nominated_by_id', 'reviewed_by', 'reviewed_at'])]
class Charity extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_anbi' => 'boolean',
            'status' => CharityStatus::class,
            'review_checklist' => 'array',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $charity): void {
            if (blank($charity->slug)) {
                $charity->slug = static::uniqueSlug($charity->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'goed-doel';
        $slug = $base;
        $counter = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (CharityStatus $status) => $status->value, array_filter(CharityStatus::cases(), fn (CharityStatus $status) => $status->receivesReservations())));
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function nominatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(CharityReservation::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(CharityPayout::class)->orderByDesc('paid_at');
    }

    public function reservedCents(): int
    {
        return (int) $this->reservations()->sum('amount_cents');
    }

    public function basisCents(): int
    {
        return (int) $this->reservations()->sum('basis_cents');
    }

    public function paidCents(): int
    {
        return (int) $this->payouts()->sum('amount_cents');
    }

    public function categoryLabel(): ?string
    {
        return $this->category ? (config('charities.categories')[$this->category] ?? $this->category) : null;
    }
}
