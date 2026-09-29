<?php

namespace Database\Factories;

use App\Domain\Edition\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Province>
 */
class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            // Cijfercodes botsen nooit met de echte tweelettercodes van de gezaaide provincies.
            'code' => sprintf('%02d', fake()->unique()->numberBetween(0, 99)),
            'sort' => 0,
        ];
    }
}
