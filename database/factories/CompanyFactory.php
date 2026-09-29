<?php

namespace Database\Factories;

use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Bakkerij '.fake()->unique()->lastName(),
            'type' => fake()->randomElement(CompanyType::cases()),
            'kvk_number' => fake()->numerify('########'),
            'website' => fake()->optional()->url(),
            'contact_name' => fake()->name(),
            'contact_phone' => '06'.fake()->numerify('########'),
            'is_archived' => false,
        ];
    }
}
