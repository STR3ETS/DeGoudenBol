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

    /**
     * De acht onderdelen uit het officiële beoordelingsrapport (aangeleverd 29 okt 2026), samen 100
     * punten. Beslisvolgorde bij gelijke stand: smaak, structuur, versheid, vulling.
     *
     * @return list<array{code: string, name: string, max_points: int, sort: int, tie_break_rank: int|null, description: string}>
     */
    public static function reportCriteria(): array
    {
        $placeholder = '[TOELICHTING VOLGT]';

        return [
            ['code' => 'smaak', 'name' => 'Smaak', 'max_points' => 25, 'sort' => 1, 'tie_break_rank' => 1, 'description' => $placeholder],
            ['code' => 'structuur_luchtigheid', 'name' => 'Structuur en luchtigheid', 'max_points' => 20, 'sort' => 2, 'tie_break_rank' => 2, 'description' => $placeholder],
            ['code' => 'versheid', 'name' => 'Versheid', 'max_points' => 15, 'sort' => 3, 'tie_break_rank' => 3, 'description' => $placeholder],
            ['code' => 'vulling_verhouding', 'name' => 'Vulling en verhouding', 'max_points' => 15, 'sort' => 4, 'tie_break_rank' => 4, 'description' => $placeholder],
            ['code' => 'uiterlijk', 'name' => 'Uiterlijk', 'max_points' => 10, 'sort' => 5, 'tie_break_rank' => null, 'description' => $placeholder],
            ['code' => 'geur', 'name' => 'Geur', 'max_points' => 5, 'sort' => 6, 'tie_break_rank' => null, 'description' => $placeholder],
            ['code' => 'vetopname_mondgevoel', 'name' => 'Vetopname en mondgevoel', 'max_points' => 5, 'sort' => 7, 'tie_break_rank' => null, 'description' => $placeholder],
            ['code' => 'totaalindruk', 'name' => 'Totaalindruk', 'max_points' => 5, 'sort' => 8, 'tie_break_rank' => null, 'description' => $placeholder],
        ];
    }
}
