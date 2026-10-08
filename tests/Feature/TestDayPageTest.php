<?php

namespace Tests\Feature;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\SessionStatus;
use App\Filament\Pages\TestDay;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsTestChain;
use Tests\TestCase;

/**
 * De testdag-cockpit: sessies van de dag met de volgende stap, versheidsklokken en leveringen.
 */
class TestDayPageTest extends TestCase
{
    use BuildsTestChain;
    use RefreshDatabase;

    #[Test]
    public function the_coordinator_sees_todays_session_with_its_next_step_and_the_freshness_clock(): void
    {
        $this->setUpTestChain(panelists: 2);

        $coordinator = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $coordinator->assignRole(StaffRole::Coordinator->value);

        $this->actingAs($coordinator)
            ->get('/admin/testdag')
            ->assertOk()
            ->assertSee('vandaag')
            ->assertSee($this->chainSession->displayName())
            ->assertSee('Afsluiten')
            ->assertSee('Monster '.$this->chainSample->label())
            ->assertSee('Dagplanning');
    }

    #[Test]
    public function the_dashboard_points_the_coordinator_to_todays_test_day(): void
    {
        $this->setUpTestChain(panelists: 1);

        $coordinator = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $coordinator->assignRole(StaffRole::Coordinator->value);

        $this->actingAs($coordinator)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Testdag vandaag')
            ->assertSee('Naar de testdag');
    }

    #[Test]
    public function a_day_without_sessions_shows_the_empty_state_with_a_jump_to_the_next_test_day(): void
    {
        $this->setUpTestChain(panelists: 1);

        $coordinator = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $coordinator->assignRole(StaffRole::Coordinator->value);

        $this->actingAs($coordinator)
            ->get('/admin/testdag?dag='.now()->subDays(3)->format('Y-m-d'))
            ->assertOk()
            ->assertSee('Geen testdag')
            ->assertSee('Volgende testdag');
    }

    #[Test]
    public function the_coordinator_closes_a_running_session_from_the_cockpit(): void
    {
        $this->setUpTestChain(panelists: 1);

        $coordinator = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $coordinator->assignRole(StaffRole::Coordinator->value);

        Livewire::actingAs($coordinator)
            ->test(TestDay::class)
            ->callAction('closeSession', arguments: ['session' => $this->chainSession->getKey()])
            ->assertHasNoErrors();

        $this->assertSame(SessionStatus::Closed, $this->chainSession->fresh()->status);
    }

    #[Test]
    public function a_reviewer_cannot_close_a_session_from_the_cockpit(): void
    {
        $this->setUpTestChain(panelists: 1);

        $reviewer = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $reviewer->assignRole(StaffRole::Reviewer->value);

        Livewire::actingAs($reviewer)
            ->test(TestDay::class)
            ->callAction('closeSession', arguments: ['session' => $this->chainSession->getKey()]);

        $this->assertSame(SessionStatus::Running, $this->chainSession->fresh()->status);
    }

    #[Test]
    public function finance_cannot_open_the_test_day(): void
    {
        $this->seed(RoleSeeder::class);

        $finance = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $finance->assignRole(StaffRole::Finance->value);

        $this->actingAs($finance)->get('/admin/testdag')->assertForbidden();
    }
}
