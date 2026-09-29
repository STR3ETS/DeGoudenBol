<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Enums\FinalistStatus;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Finale-inschrijving: de provinciewinnaar, of bij afmelding nummer 2 (besluit 17).
 * De provinciale titel blijft altijd bij nummer 1.
 *
 * @property FinalistStatus $status
 */
#[Fillable(['edition_id', 'province_id', 'entry_id', 'origin', 'province_position', 'status', 'invited_at', 'responded_at', 'replaced_by_id'])]
class Finalist extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'province_position' => 'integer',
            'status' => FinalistStatus::class,
            'invited_at' => 'immutable_datetime',
            'responded_at' => 'immutable_datetime',
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

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeParticipating(Builder $query): Builder
    {
        return $query->whereIn('status', [FinalistStatus::Invited, FinalistStatus::Confirmed]);
    }

    public function originLabel(): string
    {
        return $this->origin === 'province_winner' ? 'Provinciewinnaar' : 'Nummer 2 (winnaar afgemeld)';
    }
}
