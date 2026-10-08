<?php

namespace Tests\Feature;

use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Het bakkerdossier: één pagina per bedrijf met de inschrijvingen, accounts en orders als tabbladen.
 */
class CompanyDossierTest extends TestCase
{
    use BuildsRegistrations;
    use RefreshDatabase;

    #[Test]
    public function the_dossier_shows_the_company_with_its_registration_and_tabs(): void
    {
        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();

        $admin = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $admin->assignRole(StaffRole::Admin->value);

        $this->actingAs($admin)
            ->get(CompanyResource::getUrl('view', ['record' => $entry->company]))
            ->assertOk()
            ->assertSee($entry->company->name)
            ->assertSee('Inschrijvingen')
            ->assertSee('Orders en facturen')
            ->assertSee('Accounts')
            ->assertSee('Profieltekst');
    }

    #[Test]
    public function communication_sees_the_dossier_without_the_orders_tab(): void
    {
        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());

        $communication = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $communication->assignRole(StaffRole::Communication->value);

        $this->actingAs($communication)
            ->get(CompanyResource::getUrl('view', ['record' => $entry->company]))
            ->assertOk()
            ->assertSee($entry->company->name)
            ->assertDontSee('Orders en facturen');
    }
}
