<?php

namespace Database\Seeders;

use App\Domain\Edition\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * @return list<array{name: string, slug: string, code: string}>
     */
    public static function provinces(): array
    {
        return [
            ['name' => 'Drenthe', 'slug' => 'drenthe', 'code' => 'DR'],
            ['name' => 'Flevoland', 'slug' => 'flevoland', 'code' => 'FL'],
            ['name' => 'Friesland', 'slug' => 'friesland', 'code' => 'FR'],
            ['name' => 'Gelderland', 'slug' => 'gelderland', 'code' => 'GE'],
            ['name' => 'Groningen', 'slug' => 'groningen', 'code' => 'GR'],
            ['name' => 'Limburg', 'slug' => 'limburg', 'code' => 'LI'],
            ['name' => 'Noord-Brabant', 'slug' => 'noord-brabant', 'code' => 'NB'],
            ['name' => 'Noord-Holland', 'slug' => 'noord-holland', 'code' => 'NH'],
            ['name' => 'Overijssel', 'slug' => 'overijssel', 'code' => 'OV'],
            ['name' => 'Utrecht', 'slug' => 'utrecht', 'code' => 'UT'],
            ['name' => 'Zeeland', 'slug' => 'zeeland', 'code' => 'ZE'],
            ['name' => 'Zuid-Holland', 'slug' => 'zuid-holland', 'code' => 'ZH'],
        ];
    }

    public function run(): void
    {
        foreach (self::provinces() as $sort => $province) {
            Province::query()->updateOrCreate(
                ['slug' => $province['slug']],
                [...$province, 'sort' => $sort + 1],
            );
        }
    }
}
