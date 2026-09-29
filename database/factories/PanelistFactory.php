<?php

namespace Database\Factories;

use App\Domain\Testing\Enums\PoolStatus;
use App\Domain\Testing\Models\Panelist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Panelist>
 */
class PanelistFactory extends Factory
{
    protected $model = Panelist::class;

    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        self::$sequence++;

        return [
            'user_id' => User::factory(),
            'edition_id' => null,
            'display_code' => sprintf('P%02d', self::$sequence),
            'pool_status' => PoolStatus::Active,
            'fee_per_session_cents' => 7500,
            'allergens' => [],
            'consent_at' => now(),
        ];
    }

    /**
     * @param  list<string>  $allergens
     */
    public function allergicTo(array $allergens): static
    {
        return $this->state(fn () => ['allergens' => $allergens]);
    }
}
