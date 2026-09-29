<?php

namespace Database\Factories;

use App\Domain\Participants\Models\ParticipantUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipantUser>
 */
class ParticipantUserFactory extends Factory
{
    protected $model = ParticipantUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '06'.fake()->numerify('########'),
            'password' => 'password',
            'email_verified_at' => now(),
        ];
    }

    public function withoutPassword(): static
    {
        return $this->state(fn () => ['password' => null]);
    }
}
