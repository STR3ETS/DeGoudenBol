<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProvinceSeeder::class,
            Edition2026Seeder::class,
            PackageSeeder::class,
            ProductSeeder::class,
            RoleSeeder::class,
            LocalUserSeeder::class,
            LocalDevelopmentSeeder::class,
            LocalTestChainSeeder::class,
            LocalVoucherSeeder::class,
            LocalCampaignSeeder::class,
        ]);
    }
}
