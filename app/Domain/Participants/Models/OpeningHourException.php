<?php

namespace App\Domain\Participants\Models;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Afwijkende openingstijd op een specifieke datum, bijvoorbeeld oudjaarsdag.
 */
#[Fillable(['location_id', 'date', 'opens_at', 'closes_at', 'is_closed', 'label'])]
class OpeningHourException extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'is_closed' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
