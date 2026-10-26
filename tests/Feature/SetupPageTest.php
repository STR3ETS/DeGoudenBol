<?php

namespace Tests\Feature;

use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Pages\Setup;
use App\Models\User;
use Database\Seeders\Edition2026Seeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Inrichten: de checklist en de knop die de inschrijving echt openzet.
 */
class SetupPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, ProvinceSeeder::class, Edition2026Seeder::class]);

        $this->admin = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->admin->assignRole(StaffRole::Admin->value);
    }

    #[Test]
    public function the_page_names_what_is_missing_in_plain_language(): void
    {
        $edition = Edition::query()->where('year', 2026)->firstOrFail();
        $edition->forceFill(['status' => EditionStatus::Draft, 'registration_opens_at' => null, 'registration_closes_at' => null])->save();

        $this->actingAs($this->admin)->get('/admin/inrichten')
            ->assertOk()
            ->assertSee('De inschrijving is')
            ->assertSee('dicht')
            ->assertSee('nog niet gepubliceerd')
            ->assertSee('Deelnamevoorwaarden en privacyverklaring')
            ->assertDontSee('Inschrijving nu openzetten');

        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('Inrichting afmaken');
    }

    #[Test]
    public function the_button_opens_registration_once_everything_is_ready(): void
    {
        $edition = Edition::query()->where('year', 2026)->firstOrFail();
        $edition->forceFill(['status' => EditionStatus::Draft, 'registration_opens_at' => null, 'registration_closes_at' => null])->save();
        TermsVersion::query()->whereIn('type', [TermsType::Participation, TermsType::Privacy])->update(['published_at' => now()->subMinute()]);
        Package::factory()->create(['edition_id' => $edition->getKey()]);

        $this->get('/aanmelden')->assertOk()->assertSee('nog niet open');
        $this->actingAs($this->admin)->get('/admin/inrichten')->assertOk()->assertSee('Inschrijving nu openzetten');

        Livewire::actingAs($this->admin)->test(Setup::class)->callAction('openRegistration');

        $edition->refresh();
        $this->assertSame(EditionStatus::RegistrationOpen, $edition->status);
        $this->assertTrue($edition->isRegistrationOpen());
        $this->get('/aanmelden')->assertOk()->assertDontSee('nog niet open');

        $this->actingAs($this->admin)->get('/admin/inrichten')->assertOk()->assertSee('De inschrijving is')->assertSee('open')->assertSee('Inschrijving sluiten');

        Livewire::actingAs($this->admin)->test(Setup::class)->callAction('closeRegistration');

        $this->assertFalse($edition->refresh()->isRegistrationOpen());
        $this->get('/aanmelden')->assertOk()->assertSee('gesloten');
    }

    #[Test]
    public function the_button_refuses_while_the_terms_are_not_published(): void
    {
        $edition = Edition::query()->where('year', 2026)->firstOrFail();
        $edition->forceFill(['status' => EditionStatus::Draft, 'registration_opens_at' => null])->save();
        Package::factory()->create(['edition_id' => $edition->getKey()]);

        Livewire::actingAs($this->admin)->test(Setup::class)->callAction('openRegistration');

        $this->assertFalse($edition->refresh()->isRegistrationOpen());

        // Het concept publiceren via de knop maakt de weg vrij.
        Livewire::actingAs($this->admin)->test(Setup::class)->callAction('publishTerms');

        $this->assertNotNull(TermsVersion::latestPublished(TermsType::Participation));
        $this->assertNotNull(TermsVersion::latestPublished(TermsType::Privacy));
    }
}
