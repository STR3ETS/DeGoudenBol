<?php

namespace Tests\Feature\Portal;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\ModerationStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\ParticipantUser;
use App\Livewire\Portal\ProfileEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalProfileTest extends TestCase
{
    use RefreshDatabase;

    private ParticipantUser $owner;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = ParticipantUser::factory()->create();
        $this->company = Company::factory()->create(['name' => 'Bakkerij Profiel']);
        $this->company->users()->attach($this->owner, ['role' => CompanyUserRole::Owner]);
    }

    #[Test]
    public function guests_are_redirected_to_the_portal_login(): void
    {
        $this->get(route('portaal.profiel'))->assertRedirect(route('portaal.inloggen'));
    }

    #[Test]
    public function an_owner_can_save_a_draft_and_submit_it_for_review(): void
    {
        Livewire::actingAs($this->owner, 'participant')
            ->test(ProfileEditor::class, ['bedrijf' => $this->company->slug])
            ->assertSee('Bakkerij Profiel')
            ->set('tagline', 'Drie generaties bakkersvak')
            ->set('story', 'Wij bakken sinds 1948 in kleine batches.')
            ->set('specialtiesText', 'Oliebollen met krenten, Appelbeignets')
            ->set('street', 'Westermarkt')
            ->set('houseNumber', '14')
            ->set('postcode', '1016 dk')
            ->set('city', 'Amsterdam')
            ->set('hours.1.closed', false)
            ->set('hours.1.opens', '10:00')
            ->set('hours.1.closes', '18:00')
            ->call('save')
            ->assertHasNoErrors();

        $profile = $this->company->refresh()->profile;
        $this->assertSame(ModerationStatus::Draft, $profile->moderation_status);
        $this->assertSame(['Oliebollen met krenten', 'Appelbeignets'], $profile->specialties);
        $this->assertFalse($profile->isPublished());

        $location = $this->company->primaryLocation;
        $this->assertSame('1016 DK', $location->postcode);
        $this->assertSame('10:00:00', $location->openingHours()->where('weekday', 1)->value('opens_at'));
        $this->assertTrue((bool) $location->openingHours()->where('weekday', 2)->value('is_closed'));

        Livewire::actingAs($this->owner, 'participant')
            ->test(ProfileEditor::class, ['bedrijf' => $this->company->slug])
            ->call('submitForReview')
            ->assertHasNoErrors();

        $this->assertSame(ModerationStatus::Pending, $profile->refresh()->moderation_status);
    }

    #[Test]
    public function a_scanner_account_cannot_edit_the_profile(): void
    {
        $scanner = ParticipantUser::factory()->create();
        $this->company->users()->attach($scanner, ['role' => CompanyUserRole::Scanner]);

        $this->actingAs($scanner, 'participant')
            ->get(route('portaal.profiel', $this->company))
            ->assertForbidden();
    }

    #[Test]
    public function a_member_cannot_open_another_companys_profile(): void
    {
        $other = Company::factory()->create();

        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.profiel', $other))
            ->assertNotFound();
    }
}
