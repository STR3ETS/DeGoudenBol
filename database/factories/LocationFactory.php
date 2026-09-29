<?php

namespace Database\Factories;

use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'street' => fake()->streetName(),
            'house_number' => (string) fake()->numberBetween(1, 200),
            'postcode' => fake()->numerify('####').' '.strtoupper(fake()->lexify('??')),
            'city' => fake()->city(),
            'province_id' => Province::factory(),
            'lat' => fake()->randomFloat(6, 50.8, 53.4),
            'lng' => fake()->randomFloat(6, 3.4, 7.1),
            'is_primary' => true,
        ];
    }
}
