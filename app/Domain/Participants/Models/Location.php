<?php

namespace App\Domain\Participants\Models;

use App\Domain\Edition\Models\Province;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Adres of standplaats van een bedrijf, met seizoen en openingstijden.
 */
#[Fillable([
    'company_id',
    'label',
    'street',
    'house_number',
    'postcode',
    'city',
    'province_id',
    'lat',
    'lng',
    'is_primary',
    'season_from',
    'season_to',
])]
class Location extends DomainModel
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'is_primary' => 'boolean',
            'season_from' => 'immutable_date',
            'season_to' => 'immutable_date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class)->orderBy('weekday');
    }

    public function openingHourExceptions(): HasMany
    {
        return $this->hasMany(OpeningHourException::class)->orderBy('date');
    }

    public function fullAddress(): string
    {
        return trim("{$this->street} {$this->house_number}, {$this->postcode} {$this->city}", ' ,');
    }
}
