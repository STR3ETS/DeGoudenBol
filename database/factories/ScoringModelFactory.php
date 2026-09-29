<?php

namespace Database\Factories;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\ScoringModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoringModel>
 */
class ScoringModelFactory extends Factory
{
    protected $model = ScoringModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'version' => 1,
            'product_type' => 'oliebol',
            'name' => '100-puntenmodel',
            'is_active' => true,
        ];
    }

    /**
     * Acht onderdelen die samen precies 100 punten vormen (zelfde placeholder als de seeder).
     */
    public function withDefaultCriteria(): static
    {
        return $this->afterCreating(function (ScoringModel $model): void {
            foreach (ScoringCriterionFactory::defaultCriteria() as $criterion) {
                $model->criteria()->create($criterion);
            }
        });
    }
}
