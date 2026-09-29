<?php

namespace Database\Seeders;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Alleen lokaal: één medewerker per rol met wachtwoord "password".
 */
class LocalUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        foreach (StaffRole::cases() as $role) {
            $user = User::query()->firstOrCreate(
                ['email' => "{$role->value}@degoudenbol.test"],
                ['name' => $role->getLabel(), 'password' => 'password', 'is_active' => true],
            );

            if (! $user->hasRole($role->value)) {
                $user->assignRole($role->value);
            }
        }
    }
}
