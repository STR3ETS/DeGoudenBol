<?php

namespace Database\Factories;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Settings\EditionSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    protected $model = Edition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2026, 2099);

        return [
            'year' => $year,
            'name' => "De Gouden Bol {$year}",
            'slug' => (string) $year,
            'status' => EditionStatus::Draft,
            'settings' => new EditionSettings,
            'registration_opens_at' => "{$year}-10-12 09:00:00",
            'first_test_day' => "{$year}-11-01",
            'last_test_day' => "{$year}-12-17",
            'freeze_at' => "{$year}-12-18 12:00:00",
            'main_publication_at' => "{$year}-12-21 11:00:00",
            'final_test_day' => "{$year}-12-23",
            'national_result_at' => "{$year}-12-29 11:00:00",
            'last_redeem_day' => "{$year}-12-28",
        ];
    }

    public function registrationOpen(): static
    {
        return $this->state(fn () => ['status' => EditionStatus::RegistrationOpen]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => EditionStatus::Archived]);
    }
}
