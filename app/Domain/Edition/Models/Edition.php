<?php

namespace App\Domain\Edition\Models;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Settings\EditionSettings;
use App\Domain\Edition\Settings\EditionSettingsCast;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Eén jaargang van De Gouden Bol. Alles wat per jaar verschilt hangt hieraan.
 *
 * @property EditionSettings $settings
 * @property EditionStatus $status
 */
#[Fillable([
    'year',
    'name',
    'slug',
    'status',
    'settings',
    'registration_opens_at',
    'registration_closes_at',
    'first_test_day',
    'last_test_day',
    'freeze_at',
    'main_publication_at',
    'final_test_day',
    'national_result_at',
    'last_redeem_day',
])]
class Edition extends DomainModel
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'status' => EditionStatus::class,
            'settings' => EditionSettingsCast::class,
            'registration_opens_at' => 'immutable_datetime',
            'registration_closes_at' => 'immutable_datetime',
            'first_test_day' => 'immutable_date',
            'last_test_day' => 'immutable_date',
            'freeze_at' => 'immutable_datetime',
            'main_publication_at' => 'immutable_datetime',
            'final_test_day' => 'immutable_date',
            'national_result_at' => 'immutable_datetime',
            'last_redeem_day' => 'immutable_date',
        ];
    }

    /**
     * De lopende editie: de nieuwste die niet gearchiveerd is.
     */
    public static function current(): ?self
    {
        return static::query()->current()->first();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNot('status', EditionStatus::Archived)->orderByDesc('year');
    }

    public function provinces(): BelongsToMany
    {
        return $this->belongsToMany(Province::class)
            ->using(EditionProvince::class)
            ->withPivot(['capacity', 'reveal_at', 'revealed_at'])
            ->withTimestamps()
            ->orderBy('provinces.sort');
    }

    public function scoringModels(): HasMany
    {
        return $this->hasMany(ScoringModel::class);
    }

    public function activeScoringModel(): HasOne
    {
        return $this->hasOne(ScoringModel::class)->where('is_active', true);
    }

    public function termsVersions(): HasMany
    {
        return $this->hasMany(TermsVersion::class);
    }

    public function isRegistrationOpen(): bool
    {
        return $this->status === EditionStatus::RegistrationOpen;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
