<?php

namespace App\Domain\Edition\Models;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén onderdeel van het beoordelingsmodel, bijvoorbeeld "Smaak" met maximaal 25 punten.
 * De code is stabiel en wordt gebruikt in scorekaarten en de tiebreak-volgorde.
 */
#[Fillable(['scoring_model_id', 'code', 'name', 'description', 'max_points', 'sort', 'tie_break_rank'])]
class ScoringCriterion extends DomainModel
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_points' => 'integer',
            'sort' => 'integer',
            'tie_break_rank' => 'integer',
        ];
    }

    public function scoringModel(): BelongsTo
    {
        return $this->belongsTo(ScoringModel::class);
    }
}
