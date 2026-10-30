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
     * De acht onderdelen uit het officiële beoordelingsrapport.
     *
     * @return list<array<string, mixed>>
     */
    public static function defaultCriteria(): array
    {
        return ScoringCriterion::reportCriteria();
    }
}
