<?php

namespace Tests\Feature\Portal;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Notifications\MagicLinkNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class PortalAccountTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
    }

    #[Test]
    public function the_dashboard_shows_the_entry_and_the_invoice_after_payment(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        app(MarkOrderPaid::class)($entry->order);
        $user = $entry->company->users()->first();

        $this->actingAs($user, 'participant')
            ->get(route('portaal.dashboard'))
            ->assertOk()
            ->assertSee('Bakkerij Testers')
            ->assertSee('Aangemeld')
            ->assertSee(now()->year.'-0001');

        $invoice = $entry->order->invoice;

        $this->actingAs($user, 'participant')
            ->get(route('portaal.facturen'))
            ->assertOk()
            ->assertSee($invoice->number);

        $this->actingAs($user, 'participant')
            ->get(route('portaal.facturen.toon', $invoice))
            ->assertOk()
            ->assertSee('€ 901,45')
            ->assertSee('Bakkerij Testers');

        $stranger = ParticipantUser::factory()->create();

        $this->actingAs($stranger, 'participant')
            ->get(route('portaal.facturen.toon', $invoice))
            ->assertForbidden();
    }

    #[Test]
    public function an_owner_can_invite_staff_but_never_remove_the_last_owner(): void
    {
        Notification::fake();

        $owner = ParticipantUser::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($owner, ['role' => CompanyUserRole::Owner]);

        $this->actingAs($owner, 'participant')
            ->post(route('portaal.medewerkers.toevoegen', $company), [
                'name' => 'Sam Scanner',
                'email' => 'sam@example.test',
                'role' => 'scanner',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $sam = ParticipantUser::query()->where('email', 'sam@example.test')->firstOrFail();
        $this->assertSame(CompanyUserRole::Scanner, $sam->roleIn($company));
        Notification::assertSentTo($sam, MagicLinkNotification::class);

        $this->actingAs($sam, 'participant')
            ->get(route('portaal.medewerkers', $company))
            ->assertForbidden();

        $this->actingAs($owner, 'participant')
            ->delete(route('portaal.medewerkers.verwijderen', [$company, $owner]))
            ->assertSessionHasErrors('email');

        $this->actingAs($owner, 'participant')
            ->delete(route('portaal.medewerkers.verwijderen', [$company, $sam]))
            ->assertSessionHas('status');

        $this->assertNull($sam->refresh()->roleIn($company));
    }

    #[Test]
    public function a_participant_without_password_can_set_one_and_log_in_with_it(): void
    {
        $user = ParticipantUser::factory()->withoutPassword()->create(['email' => 'nieuw@example.test']);

        $this->actingAs($user, 'participant')
            ->put(route('portaal.wachtwoord.opslaan'), [
                'password' => 'een-lang-wachtwoord-123',
                'password_confirmation' => 'een-lang-wachtwoord-123',
            ])
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('een-lang-wachtwoord-123', $user->refresh()->password));

        auth('participant')->logout();

        $this->post(route('portaal.inloggen.verwerken'), ['email' => 'NIEUW@example.test', 'password' => 'een-lang-wachtwoord-123'])
            ->assertRedirect(route('portaal.dashboard'));

        $this->assertAuthenticatedAs($user, 'participant');
    }

    #[Test]
    public function the_password_reset_flow_uses_the_participant_broker_and_portal_routes(): void
    {
        Notification::fake();

        $user = ParticipantUser::factory()->create(['email' => 'reset@example.test']);

        $this->post(route('portaal.wachtwoord.vergeten.versturen'), ['email' => 'reset@example.test'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = call_user_func(ResetPassword::$createUrlCallback, $user, $notification->token);
            $this->assertStringContainsString('/portaal/wachtwoord-herstellen/', $url);

            $this->post(route('portaal.wachtwoord.herstellen.opslaan'), [
                'token' => $notification->token,
                'email' => 'reset@example.test',
                'password' => 'nog-een-lang-wachtwoord',
                'password_confirmation' => 'nog-een-lang-wachtwoord',
            ])->assertRedirect(route('portaal.dashboard'));

            return true;
        });

        $this->assertTrue(Hash::check('nog-een-lang-wachtwoord', $user->refresh()->password));
    }
}
