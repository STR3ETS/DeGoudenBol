<?php

namespace Tests\Feature;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Exceptions\ForbiddenRoleCombinationException;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleAssignmentGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function a_panelist_can_never_also_do_intake(): void
    {
        $user = User::factory()->create();
        $user->assignRole(StaffRole::Panelist->value);

        $this->expectException(ForbiddenRoleCombinationException::class);

        $user->assignRole(StaffRole::Intake->value);
    }

    #[Test]
    public function intake_staff_can_never_also_be_a_panelist(): void
    {
        $user = User::factory()->create();
        $user->assignRole(StaffRole::Intake->value);

        $this->expectException(ForbiddenRoleCombinationException::class);

        $user->assignRole(StaffRole::Panelist);
    }

    #[Test]
    public function a_panelist_can_never_also_publish(): void
    {
        $user = User::factory()->create();

        $this->expectException(ForbiddenRoleCombinationException::class);

        $user->assignRole([StaffRole::Panelist->value, StaffRole::Publisher->value]);
    }

    #[Test]
    public function sync_roles_is_guarded_as_well(): void
    {
        $user = User::factory()->create();

        $this->expectException(ForbiddenRoleCombinationException::class);

        $user->syncRoles([StaffRole::Publisher->value, StaffRole::Panelist->value]);
    }

    #[Test]
    public function allowed_combinations_still_work(): void
    {
        $user = User::factory()->create();

        $user->assignRole(StaffRole::Admin->value, StaffRole::Finance->value);
        $user->assignRole(StaffRole::Reviewer->value);

        $this->assertEqualsCanonicalizing(
            [StaffRole::Admin, StaffRole::Finance, StaffRole::Reviewer],
            $user->fresh()->staffRoles(),
        );
    }

    #[Test]
    public function the_forbidden_combinations_are_symmetric(): void
    {
        $this->assertTrue(StaffRole::Panelist->conflictsWith(StaffRole::Intake));
        $this->assertTrue(StaffRole::Intake->conflictsWith(StaffRole::Panelist));
        $this->assertTrue(StaffRole::Publisher->conflictsWith(StaffRole::Panelist));
        $this->assertFalse(StaffRole::Admin->conflictsWith(StaffRole::Finance));
        $this->assertFalse(StaffRole::Panelist->conflictsWith(StaffRole::Coordinator));
    }
}
