<?php

namespace Database\Seeders;

use App\Domain\Platform\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (StaffRole::cases() as $role) {
            Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }
    }
}
