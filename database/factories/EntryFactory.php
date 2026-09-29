<?php

namespace Database\Factories;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    protected $model = Entry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'company_id' => Company::factory(),
            'province_id' => Province::factory(),
            'status' => EntryStatus::Registered,
            'public_name' => 'Bakkerij '.fake()->lastName(),
            'tagline' => fake()->optional()->sentence(5),
            'allergens' => ['gluten', 'melk', 'ei'],
            'registered_at' => now(),
            'confirmed_at' => now(),
        ];
    }

    public function pendingPayment(int $minutes = 60): static
    {
        return $this->state(fn () => [
            'status' => EntryStatus::PendingPayment,
            'confirmed_at' => null,
            'reservation_expires_at' => now()->addMinutes($minutes),
        ]);
    }

    public function expiredReservation(): static
    {
        return $this->state(fn () => [
            'status' => EntryStatus::PendingPayment,
            'confirmed_at' => null,
            'reservation_expires_at' => now()->subMinute(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => EntryStatus::Cancelled, 'confirmed_at' => null]);
    }
}
