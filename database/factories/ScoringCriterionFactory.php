<?php

namespace Database\Factories;

use App\Domain\Edition\Models\ScoringCriterion;
use App\Domain\Edition\Models\ScoringModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ScoringCriterion>
 */
class ScoringCriterionFactory extends Factory
{
    protected $model = ScoringCriterion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'scoring_model_id' => ScoringModel::factory(),
            'code' => Str::snake($name),
            'name' => $name,
            'description' => fake()->sentence(),
            'max_points' => fake()->numberBetween(5, 25),
            'sort' => fake()->numberBetween(1, 8),
            'tie_break_rank' => null,
        ];
    }

    /**
     * AANNAME: zeker uit de briefing zijn Smaak 25, Structuur en luchtigheid 20, 15 punten over
     * vulling en de tiebreak-volgorde. De overige verdeling vult aan tot 100 en wordt vervangen
     * zodra het Conceptdossier beschikbaar is.
     *
     * @return list<array<string, mixed>>
     */
    public static function defaultCriteria(): array
    {
        $placeholder = '[TOELICHTING VOLGT UIT HET CONCEPTDOSSIER]';

        return [
            ['code' => 'smaak', 'name' => 'Smaak', 'max_points' => 25, 'sort' => 1, 'tie_break_rank' => 1, 'description' => $placeholder],
            ['code' => 'structuur_luchtigheid', 'name' => 'Structuur en luchtigheid', 'max_points' => 20, 'sort' => 2, 'tie_break_rank' => 2, 'description' => $placeholder],
            ['code' => 'vulling_verhouding', 'name' => 'Vulling en verhouding', 'max_points' => 15, 'sort' => 3, 'tie_break_rank' => 4, 'description' => $placeholder],
            ['code' => 'versheid', 'name' => 'Versheid', 'max_points' => 10, 'sort' => 4, 'tie_break_rank' => 3, 'description' => $placeholder],
            ['code' => 'korst_kleur', 'name' => 'Korst en kleur', 'max_points' => 10, 'sort' => 5, 'tie_break_rank' => null, 'description' => $placeholder],
            ['code' => 'bakgraad_vetopname', 'name' => 'Bakgraad en vetopname', 'max_points' => 10, 'sort' => 6, 'tie_break_rank' => null, 'description' => $placeholder],
            ['code' => 'geur', 'name' => 'Geur', 'max_points' => 5, 'sort' => 7, 'tie_break_rank' => null, 'description' => $placeholder],
            ['code' => 'uiterlijk_presentatie', 'name' => 'Uiterlijk en presentatie', 'max_points' => 5, 'sort' => 8, 'tie_break_rank' => null, 'description' => $placeholder],
        ];
    }
}
