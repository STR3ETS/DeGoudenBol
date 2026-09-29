<?php

namespace Tests\Feature;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Resources\Charities\CharityResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\ConfidentialReports\ConfidentialReportResource;
use App\Filament\Resources\CorrectionCases\CorrectionCaseResource;
use App\Filament\Resources\DeliverySlots\DeliverySlotResource;
use App\Filament\Resources\Editions\EditionResource;
use App\Filament\Resources\Entries\EntryResource;
use App\Filament\Resources\Finalists\FinalistResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\MediaContacts\MediaContactResource;
use App\Filament\Resources\NewsletterSubscriptions\NewsletterSubscriptionResource;
use App\Filament\Resources\NewsPosts\NewsPostResource;
use App\Filament\Resources\Objections\ObjectionResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Packages\PackageResource;
use App\Filament\Resources\Panelists\PanelistResource;
use App\Filament\Resources\ParticipantUsers\ParticipantUserResource;
use App\Filament\Resources\PressReleases\PressReleaseResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Profiles\ProfileResource;
use App\Filament\Resources\Provinces\ProvinceResource;
use App\Filament\Resources\PublicationBatches\PublicationBatchResource;
use App\Filament\Resources\Recognitions\RecognitionResource;
use App\Filament\Resources\Samples\SampleResource;
use App\Filament\Resources\ScoringModels\ScoringModelResource;
use App\Filament\Resources\Sponsors\SponsorResource;
use App\Filament\Resources\TermsVersions\TermsVersionResource;
use App\Filament\Resources\TestLocations\TestLocationResource;
use App\Filament\Resources\TestSessions\TestSessionResource;
use App\Filament\Resources\TieBreakRounds\TieBreakRoundResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\VoucherCampaigns\VoucherCampaignResource;
use App\Models\User;
use Database\Seeders\Edition2026Seeder;
use Database\Seeders\PackageSeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Rooktest: iedere backoffice-pagina rendert voor een beheerder met ingestelde tweestapsverificatie.
 */
class AdminResourcesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, ProvinceSeeder::class, Edition2026Seeder::class, PackageSeeder::class]);

        $this->admin = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->admin->assignRole(StaffRole::Admin->value);
    }

    #[Test]
    public function the_login_page_renders_with_the_brand(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('De Gouden Bol');
    }

    #[Test]
    public function the_dashboard_renders_with_the_edition_widgets(): void
    {
        $this->actingAs($this->admin)->get('/admin')
            ->assertOk()
            ->assertSee(explode(' ', $this->admin->name)[0])
            ->assertSee('Bevestigde deelnemers')
            ->assertSee('van 600 plekken')
            ->assertSee('Mijlpalen 2026')
            ->assertSee('Hoofdpublicatie')
            ->assertSee('Plekken per provincie')
            ->assertSee('Gelderland')
            ->assertSee('in 14 dagen')
            ->assertSee('Snel naar');
    }

    #[Test]
    public function every_list_page_renders(): void
    {
        foreach ([
            EditionResource::class,
            ProvinceResource::class,
            ScoringModelResource::class,
            TestLocationResource::class,
            TermsVersionResource::class,
            UserResource::class,
            CompanyResource::class,
            EntryResource::class,
            ParticipantUserResource::class,
            ProfileResource::class,
            OrderResource::class,
            InvoiceResource::class,
            PackageResource::class,
            NewsPostResource::class,
            DeliverySlotResource::class,
            PanelistResource::class,
            TestSessionResource::class,
            SampleResource::class,
            PublicationBatchResource::class,
            ConfidentialReportResource::class,
            ObjectionResource::class,
            FinalistResource::class,
            TieBreakRoundResource::class,
            CorrectionCaseResource::class,
            RecognitionResource::class,
            VoucherCampaignResource::class,
            PressReleaseResource::class,
            MediaContactResource::class,
            SponsorResource::class,
            ProductResource::class,
            CharityResource::class,
            NewsletterSubscriptionResource::class,
        ] as $resource) {
            $this->actingAs($this->admin)
                ->get($resource::getUrl('index'))
                ->assertOk();
        }
    }

    #[Test]
    public function resources_are_hidden_for_roles_that_may_not_see_them(): void
    {
        $finance = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $finance->assignRole(StaffRole::Finance->value);

        $this->actingAs($finance)->get(OrderResource::getUrl('index'))->assertOk();
        $this->actingAs($finance)->get(EditionResource::getUrl('index'))->assertForbidden();
        $this->actingAs($finance)->get(ProfileResource::getUrl('index'))->assertForbidden();

        $communication = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $communication->assignRole(StaffRole::Communication->value);

        $this->actingAs($communication)->get(ProfileResource::getUrl('index'))->assertOk();
        $this->actingAs($communication)->get(OrderResource::getUrl('index'))->assertForbidden();
    }

    #[Test]
    public function the_intake_page_is_only_for_the_intake_role(): void
    {
        $this->actingAs($this->admin)->get('/admin/ontvangst')->assertForbidden();

        $intake = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $intake->assignRole(StaffRole::Intake->value);

        $this->actingAs($intake)->get('/admin/ontvangst')
            ->assertOk()
            ->assertSee('Aanleverbewijs scannen')
            ->assertSee('Direct testnummer toekennen');
    }

    #[Test]
    public function the_edition_view_and_edit_pages_render_with_settings(): void
    {
        $edition = Edition::query()->where('year', 2026)->firstOrFail();

        $this->actingAs($this->admin)
            ->get(EditionResource::getUrl('view', ['record' => $edition]))
            ->assertOk()
            ->assertSee('De Gouden Bol 2026');

        $this->actingAs($this->admin)
            ->get(EditionResource::getUrl('edit', ['record' => $edition]))
            ->assertOk()
            ->assertSee('Plekken per provincie');
    }

    #[Test]
    public function the_scoring_model_edit_page_lists_the_criteria(): void
    {
        $model = ScoringModel::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->get(ScoringModelResource::getUrl('edit', ['record' => $model]))
            ->assertOk()
            ->assertSee('100-puntenmodel oliebol 2026')
            ->assertSee('CriteriaRelationManager');
    }

    #[Test]
    public function the_user_edit_page_renders_the_role_field(): void
    {
        $this->actingAs($this->admin)
            ->get(UserResource::getUrl('edit', ['record' => $this->admin]))
            ->assertOk()
            ->assertSee('Rollen');
    }
}
