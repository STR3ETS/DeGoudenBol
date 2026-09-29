<?php

namespace App\Domain\Edition\Models;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Geversioneerd beoordelingsmodel: de onderdelen waarop het panel scoort, samen 100 punten.
 */
#[Fillable(['edition_id', 'version', 'product_type', 'name', 'is_active'])]
class ScoringModel extends DomainModel
{
    use HasFactory;

    public const int TOTAL_POINTS = 100;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(ScoringCriterion::class)->orderBy('sort');
    }

    public function totalMaxPoints(): int
    {
        return (int) $this->criteria()->sum('max_points');
    }

    public function sumsToHundred(): bool
    {
        return $this->totalMaxPoints() === self::TOTAL_POINTS;
    }
}
