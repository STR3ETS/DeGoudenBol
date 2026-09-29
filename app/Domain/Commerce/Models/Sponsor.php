<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Charities\Models\Charity;
use App\Domain\Commerce\Enums\SponsorStatus;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sponsor: logo, link en contact. Ziet nooit testgegevens; sponsor–deelnemer bestaat niet in het testdomein.
 *
 * @property SponsorStatus $status
 */
#[Fillable(['name', 'slug', 'url', 'logo_path', 'contact_name', 'contact_email', 'contact_phone', 'kvk_number', 'billing_address', 'notes', 'status'])]
class Sponsor extends DomainModel
{
    use HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'status' => SponsorStatus::class,
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $sponsor): void {
            if (blank($sponsor->slug)) {
                $sponsor->slug = static::uniqueSlug($sponsor->name);
            }

            $sponsor->status ??= SponsorStatus::Active;
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'sponsor';
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SponsorStatus::Active);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(SponsorLink::class);
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function nomination(): MorphOne
    {
        return $this->morphOne(Charity::class, 'nominatedBy');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
