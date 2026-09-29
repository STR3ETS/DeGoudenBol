<?php

namespace App\Domain\Participants\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\CompanyType;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Een bakkerij, kraam of ander bedrijf. Blijft over edities heen bestaan;
 * de inschrijving en het resultaat hangen aan een editie.
 */
#[Fillable([
    'name',
    'slug',
    'type',
    'kvk_number',
    'founded_year',
    'website',
    'contact_name',
    'contact_phone',
    'socials',
    'logo_path',
    'is_archived',
])]
class Company extends DomainModel
{
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CompanyType::class,
            'founded_year' => 'integer',
            'socials' => 'array',
            'is_archived' => 'boolean',
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
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $company): void {
            if (blank($company->slug)) {
                $company->slug = self::uniqueSlug($company->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'bakker';
        $slug = $base;
        $counter = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(ParticipantUser::class, 'company_user')
            ->using(CompanyUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function entryFor(Edition $edition): ?Entry
    {
        return $this->entries()->where('edition_id', $edition->getKey())->latest('id')->first();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class)->orderByDesc('is_primary');
    }

    public function primaryLocation(): HasOne
    {
        return $this->hasOne(Location::class)->where('is_primary', true);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }
}
