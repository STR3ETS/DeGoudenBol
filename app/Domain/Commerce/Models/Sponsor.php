<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Charities\Models\Charity;
use App\Domain\Commerce\Enums\SponsorStatus;
use App\Domain\Edition\Models\Province;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Sponsor: logo, link en contact. Ziet nooit testgegevens; sponsor–deelnemer bestaat niet in het testdomein.
 *
 * @property SponsorStatus $status
 */
#[Fillable(['name', 'slug', 'url', 'logo_path', 'contact_name', 'contact_email', 'contact_phone', 'kvk_number', 'billing_address', 'notes', 'status', 'source', 'requested_product_id', 'requested_province_id', 'request_note', 'requested_at', 'approved_at'])]
class Sponsor extends DomainModel
{
    use HasUlids;

    /** Zo lang werkt de beheerlink die een sponsor per mail krijgt. */
    public const int ACCESS_LINK_DAYS = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'status' => SponsorStatus::class,
            'requested_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function requestedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'requested_product_id');
    }

    public function requestedProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'requested_province_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(SponsorOffer::class);
    }

    /**
     * Persoonlijke beheerlink naar de sponsoromgeving (pakket, facturen, koppelingen, acties).
     */
    public function accessUrl(): string
    {
        return URL::temporarySignedRoute('sponsor.toegang', now()->addDays(self::ACCESS_LINK_DAYS), ['sponsor' => $this]);
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
        return $this->morphOne(Charity::class, 'nominated_by');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
