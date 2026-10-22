<?php

namespace Tests\Feature;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\Edition2026Seeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * De fout van 21 oktober: inschrijving open, maar de aanmeldpagina dicht omdat de voorwaarden
 * nog niet gepubliceerd waren. Het dashboard moet dat als eerste werkregel tonen.
 */
class RegistrationTermsWorkItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_dashboard_warns_when_registration_is_open_without_published_terms(): void
    {
        $this->seed([RoleSeeder::class, ProvinceSeeder::class, Edition2026Seeder::class]);

        $edition = Edition::query()->where('year', 2026)->firstOrFail();
        $edition->forceFill(['status' => EditionStatus::RegistrationOpen, 'registration_opens_at' => now()->subDay(), 'registration_closes_at' => now()->addMonth()])->save();

        $admin = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $admin->assignRole(StaffRole::Admin->value);

        // De gezaaide voorwaarden zijn concepten: de site blijft dicht en het dashboard waarschuwt.
        $this->get('/aanmelden')->assertOk()->assertSee('nog niet open')->assertSee('opent binnenkort');
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Deelnamevoorwaarden publiceren')->assertSee('deelnamevoorwaarden en privacyverklaring');

        TermsVersion::query()->whereIn('type', [TermsType::Participation, TermsType::Privacy])->update(['published_at' => now()->subMinute()]);

        $this->get('/aanmelden')->assertOk()->assertDontSee('nog niet open')->assertSee('officieel keuren');
        $this->app->forgetScopedInstances(); // per verzoek een verse DashboardData, zoals in productie (eigen PHP-proces)
        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee('Deelnamevoorwaarden publiceren');
    }
}
