<?php

namespace Tests\Feature\Portal;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\PortalViewing;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Models\AuditLog;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Livewire\Portal\ProfileEditor;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Meekijken in het portaal van een bakker (verzoek Gill, 10 nov 2026): alleen lezen, alleen voor
 * beheer, ontvangst en financiën, vastgelegd in de auditlog en zonder gevolgen voor de bakker zelf.
 */
class PortalViewingTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $entry;

    private Company $company;

    private ParticipantUser $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        app(MarkOrderPaid::class)($this->entry->order);

        $this->company = $this->entry->company;
        $this->owner = $this->company->owners()->first();
    }

    #[Test]
    public function staff_open_the_portal_of_a_baker_from_the_dossier_and_it_is_logged(): void
    {
        $admin = $this->staff(StaffRole::Admin);

        Livewire::actingAs($admin)
            ->test(ViewCompany::class, ['record' => $this->company->getRouteKey()])
            ->assertActionVisible('viewPortal')
            ->callAction('viewPortal')
            ->assertRedirect(route('portaal.dashboard'));

        $this->assertTrue(Auth::guard('participant')->check());
        $this->assertTrue($this->owner->is(Auth::guard('participant')->user()));

        $log = AuditLog::query()->where('action', 'portal.viewing_started')->sole();

        $this->assertSame($admin->getKey(), $log->actor_id);
        $this->assertSame($this->company->getKey(), $log->subject_id);
        $this->assertSame($this->owner->getKey(), $log->payload['participant_user_id']);

        $this->get(route('portaal.dashboard'))
            ->assertOk()
            ->assertSee('Je kijkt mee in het portaal van Bakkerij Testers')
            ->assertSee('Stop meekijken')
            ->assertDontSee('Uitloggen');

        $this->get(route('portaal.aanmelding', $this->company))->assertOk();

        // De bakker merkt er niets van: geen "laatste login" en geen onthoud-mij-token.
        $this->assertNull($this->owner->fresh()->last_login_at);
        $this->assertNull($this->owner->fresh()->remember_token);
    }

    #[Test]
    public function while_viewing_nothing_can_be_changed_and_report_and_result_stay_closed(): void
    {
        $this->startViewing($this->staff(StaffRole::Intake));

        $this->from(route('portaal.wachtwoord'))
            ->put(route('portaal.wachtwoord.opslaan'), ['password' => 'Nieuw-Wachtwoord-2026!', 'password_confirmation' => 'Nieuw-Wachtwoord-2026!'])
            ->assertRedirect(route('portaal.wachtwoord'))
            ->assertSessionHas('status', 'Je kijkt mee: wijzigen kan niet.');

        $this->assertFalse(Hash::check('Nieuw-Wachtwoord-2026!', (string) $this->owner->fresh()->password));

        $this->post(route('portaal.medewerkers.toevoegen', $this->company), ['name' => 'Indringer', 'email' => 'indringer@example.test', 'role' => 'staff'])
            ->assertRedirect();

        $this->assertSame(1, $this->company->users()->count());

        // Het gewone uitloggen zou ook de backoffice-sessie wissen; tijdens het meekijken is het dicht.
        $this->post(route('portaal.uitloggen'))->assertRedirect();
        $this->assertTrue(Auth::guard('web')->check());

        foreach (['portaal.rapport', 'portaal.uitslag', 'portaal.oorkonde'] as $route) {
            $this->get(route($route, $this->company))
                ->assertRedirect(route('portaal.dashboard'))
                ->assertSessionHas('status', 'Rapport en uitslag blijven dicht tijdens het meekijken.');
        }

        Livewire::test(ProfileEditor::class, ['bedrijf' => $this->company->slug])
            ->set('tagline', 'Aangepast door een medewerker')
            ->call('save')
            ->call('submitForReview');

        $this->assertNotSame('Aangepast door een medewerker', $this->company->fresh()->profile?->tagline);
    }

    #[Test]
    public function only_roles_that_already_know_the_participant_may_view(): void
    {
        $this->assertTrue(PortalViewing::allows($this->staff(StaffRole::Finance)));

        foreach ([StaffRole::Communication, StaffRole::Coordinator, StaffRole::Reviewer, StaffRole::Panelist, StaffRole::Publisher, StaffRole::VoucherManager] as $role) {
            $this->assertFalse(PortalViewing::allows($this->staff($role)), $role->value);
        }

        $communication = $this->staff(StaffRole::Communication);

        Livewire::actingAs($communication)
            ->test(ViewCompany::class, ['record' => $this->company->getRouteKey()])
            ->assertActionHidden('viewPortal');

        $this->assertFalse(Auth::guard('participant')->check());
        $this->assertSame(0, AuditLog::query()->where('action', 'portal.viewing_started')->count());

        $this->expectException(AuthorizationException::class);

        app(PortalViewing::class)->start($communication, $this->company, $this->owner);
    }

    #[Test]
    public function stopping_returns_to_the_dossier_and_keeps_the_staff_member_logged_in(): void
    {
        $this->owner->forceFill(['remember_token' => 'token-van-de-bakker'])->save();

        $this->startViewing($this->staff(StaffRole::Admin));

        $this->post(route('portaal.meekijken.stoppen'))
            ->assertRedirect(CompanyResource::getUrl('view', ['record' => $this->company]));

        $this->assertFalse(Auth::guard('participant')->check());
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertFalse(session()->has(PortalViewing::SESSION_KEY));

        // De bakker blijft op zijn eigen apparaten ingelogd.
        $this->assertSame('token-van-de-bakker', $this->owner->fresh()->remember_token);

        $this->get(route('portaal.dashboard'))->assertRedirect(route('portaal.inloggen'));
    }

    #[Test]
    public function viewing_ends_when_the_time_is_up_or_the_staff_member_is_gone(): void
    {
        $admin = $this->staff(StaffRole::Admin);
        $this->startViewing($admin);

        $this->travel(PortalViewing::MINUTES + 1)->minutes();

        $this->get(route('portaal.dashboard'))->assertRedirect(route('portaal.inloggen'));
        $this->assertFalse(Auth::guard('participant')->check());

        $this->travelBack();
        $this->startViewing($admin);
        $admin->syncRoles([StaffRole::Communication->value]);

        $this->get(route('portaal.dashboard'))->assertRedirect(route('portaal.inloggen'));
        $this->assertFalse(Auth::guard('participant')->check());
    }

    #[Test]
    public function the_baker_logging_in_normally_is_never_restricted(): void
    {
        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.dashboard'))
            ->assertOk()
            ->assertDontSee('Je kijkt mee')
            ->assertSee('Uitloggen');

        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.uitslag', $this->company))
            ->assertOk();
    }

    private function staff(StaffRole $role): User
    {
        $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $user->assignRole($role->value);

        return $user;
    }

    private function startViewing(User $staff): void
    {
        $this->actingAs($staff);

        app(PortalViewing::class)->start($staff, $this->company, $this->owner);
    }
}
