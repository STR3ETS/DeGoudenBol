<?php

namespace Database\Factories;

use App\Domain\Edition\Models\Edition;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Models\TestSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestSession>
 */
class TestSessionFactory extends Factory
{
    protected $model = TestSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addHour()->startOfHour();

        return [
            'edition_id' => Edition::factory(),
            'round' => SampleRound::Provincial,
            'name' => 'Sessie '.fake()->unique()->numberBetween(1, 999),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addHours(2),
            'status' => SessionStatus::Planned,
            'max_samples' => 8,
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'status' => SessionStatus::Running,
            'starts_at' => now()->subMinutes(15),
            'ends_at' => now()->addHours(2),
        ]);
    }
}
