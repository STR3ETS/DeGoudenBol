<?php

namespace Database\Factories;

use App\Domain\Edition\Models\TestLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestLocation>
 */
class TestLocationFactory extends Factory
{
    protected $model = TestLocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Testlocatie '.fake()->city(),
            'street' => fake()->streetAddress(),
            'postcode' => fake()->postcode(),
            'city' => fake()->city(),
            'notes' => null,
            'capacity' => 16,
            'is_active' => true,
        ];
    }
}
