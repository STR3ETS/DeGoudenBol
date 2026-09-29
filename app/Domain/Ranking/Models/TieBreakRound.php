<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Ranking\Enums\TieBreakStatus;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Beslisronde: gelijke deelnemers op plaats 1 of op de grens van de Top 10 worden opnieuw blind
 * getest (nieuwe monsters B01…). De uitkomst bepaalt alleen hun onderlinge volgorde.
 *
 * @property list<int> $entry_ids
 * @property array<int|string, array{total_raw: float, criterion_averages: array<string, float>}>|null $scores
 * @property list<int>|null $outcome_order
 * @property TieBreakStatus $status
 */
#[Fillable(['edition_id', 'province_id', 'scope', 'position', 'key', 'entry_ids', 'scores', 'outcome_order', 'status', 'decided_at'])]
class TieBreakRound extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'entry_ids' => 'array',
            'scores' => 'array',
            'outcome_order' => 'array',
            'status' => TieBreakStatus::class,
            'decided_at' => 'immutable_datetime',
        ];
    }

    /**
     * @param  list<int>  $entryIds
     */
    public static function keyFor(int $position, array $entryIds): string
    {
        sort($entryIds);

        return hash('sha256', $position.':'.implode(',', $entryIds));
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function isDecided(): bool
    {
        return $this->status === TieBreakStatus::Decided;
    }

    public function scopeLabel(): string
    {
        return $this->scope === 'position_1' ? 'Plaats 1' : 'Grens van de Top 10';
    }
}
