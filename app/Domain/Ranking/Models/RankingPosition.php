<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Enums\PositionLabel;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PositionLabel $label
 */
#[Fillable(['ranking_snapshot_id', 'entry_id', 'position', 'total', 'tie_group', 'label', 'needs_tie_break'])]
class RankingPosition extends DomainModel
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'total' => 'float',
            'tie_group' => 'integer',
            'label' => PositionLabel::class,
            'needs_tie_break' => 'boolean',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(RankingSnapshot::class, 'ranking_snapshot_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function formattedTotal(): string
    {
        return number_format($this->total, 1, ',', '.');
    }
}
