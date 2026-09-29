<?php

namespace Database\Factories;

use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'code' => 'deelname-'.fake()->unique()->lexify('????'),
            'name' => 'Deelname (basis)',
            'description' => 'Deelname aan de keuring met vertrouwelijk rapport.',
            'price_cents' => 74500,
            'vat_rate' => 21.0,
            'is_base' => true,
            'is_active' => true,
            'sort' => 1,
            'entitlements' => ['badge_participant', 'social_kit', 'confidential_report'],
        ];
    }
}
