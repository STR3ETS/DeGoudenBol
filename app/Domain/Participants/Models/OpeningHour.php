<?php

namespace App\Domain\Participants\Models;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reguliere openingstijd per weekdag (1 = maandag t/m 7 = zondag, ISO-8601).
 */
#[Fillable(['location_id', 'weekday', 'opens_at', 'closes_at', 'is_closed'])]
class OpeningHour extends DomainModel
{
    public const array WEEKDAYS = [
        1 => 'Maandag',
        2 => 'Dinsdag',
        3 => 'Woensdag',
        4 => 'Donderdag',
        5 => 'Vrijdag',
        6 => 'Zaterdag',
        7 => 'Zondag',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function weekdayLabel(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? (string) $this->weekday;
    }
}
