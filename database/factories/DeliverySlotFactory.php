<?php

namespace Database\Factories;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Models\DeliverySlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliverySlot>
 */
class DeliverySlotFactory extends Factory
{
    protected $model = DeliverySlot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(3)->setTime(9, 0);

        return [
            'edition_id' => Edition::factory(),
            'test_location_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addHour(),
            'capacity' => 10,
        ];
    }
}
