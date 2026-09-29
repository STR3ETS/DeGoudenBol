<?php

namespace Database\Factories;

use App\Domain\Edition\Models\Edition;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sample>
 */
class SampleFactory extends Factory
{
    protected $model = Sample::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'round' => SampleRound::Provincial,
            'sample_number' => null,
            'status' => SampleStatus::Received,
        ];
    }

    public function numbered(int $sequence): static
    {
        return $this->state(fn (array $attributes) => [
            'sample_number' => ($attributes['round'] ?? SampleRound::Provincial)->formatNumber($sequence),
            'status' => SampleStatus::Numbered,
        ]);
    }

    public function withIntake(int $minutesAgo = 10, int $windowMinutes = 180): static
    {
        return $this->afterCreating(function (Sample $sample) use ($minutesAgo, $windowMinutes): void {
            $receivedAt = now()->subMinutes($minutesAgo);

            $sample->intake()->create([
                'received_at' => $receivedAt,
                'temperature_c' => 21.5,
                'piece_count' => 8,
                'freshness_expires_at' => $receivedAt->addMinutes($windowMinutes),
            ]);
        });
    }
}
