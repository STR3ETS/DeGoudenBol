<?php

namespace Tests\Feature;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    #[Test]
    public function users_without_a_staff_role_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    #[Test]
    public function inactive_staff_are_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole(StaffRole::Admin->value);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    #[Test]
    public function active_staff_without_mfa_are_sent_to_the_mfa_set_up(): void
    {
        $user = User::factory()->create();
        $user->assignRole(StaffRole::Admin->value);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect();
        $this->assertStringContainsString('multi-factor-authentication', $response->headers->get('Location'));
    }
}
