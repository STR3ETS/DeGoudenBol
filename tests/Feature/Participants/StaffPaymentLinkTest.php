<?php

namespace Tests\Feature\Participants;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Actions\CancelExpiredReservations;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\TermsAcceptance;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Models\AuditLog;
use App\Filament\Resources\Entries\Pages\ManageEntries;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Aanmelding met betaallink (verzoek Bennie, 12 nov 2026): de organisatie zet de aanmelding klaar,
 * de bakker gaat op de betaalpagina zelf akkoord met de voorwaarden en betaalt.
 */
class StaffPaymentLinkTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::fake(['api.pdok.nl/*' => Http::response(['response' => ['docs' => []]])]);

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();
        $this->edition->forceFill(['settings' => $this->edition->settings->with([
            'bundle_cents' => 19500,
            'bundle_regular_cents' => 39000,
            'video_addon_cents' => 35000,
        ])])->save();
    }

    #[Test]
    public function staff_prepare_a_registration_and_get_a_link_that_stays_valid_for_the_chosen_days(): void
    {
        $admin = $this->staff(StaffRole::Admin);

        Livewire::actingAs($admin)
            ->test(ManageEntries::class)
            ->assertActionVisible('createWithPaymentLink')
            ->callAction('createWithPaymentLink', data: $this->formData(['video_addon' => true, 'valid_days' => 10]))
            ->assertHasNoActionErrors()
            ->assertNotified('Betaallink voor Bakkerij Telefoon');

        $entry = Entry::query()->with(['order.lines', 'company.primaryLocation'])->sole();

        $this->assertSame(EntryStatus::PendingPayment, $entry->status);
        $this->assertSame(EntryRate::Bundle, $entry->rate);
        $this->assertTrue($entry->video_addon);
        $this->assertNull($entry->terms_acceptance_id);
        $this->assertSame('Bakkerij Telefoon', $entry->public_name);
        $this->assertSame('6811AB', $entry->company->primaryLocation->postcode);
        $this->assertSame(65945, $entry->order->total_cents); // (EUR 195 + EUR 350) + 21% btw
        $this->assertEqualsWithDelta(now()->addDays(10)->getTimestamp(), $entry->reservation_expires_at->getTimestamp(), 5);

        $log = AuditLog::query()->where('action', 'entry.registered')->sole();
        $this->assertSame($admin->getKey(), $log->actor_id);
        $this->assertTrue($log->payload['prepared_by_staff']);

        // Na een dag kan er nog steeds betaald worden; een gewone aanmelding is dan al lang verlopen.
        $this->travel(1)->days();
        app(CancelExpiredReservations::class)();

        $this->assertTrue($entry->fresh()->canPay());

        Livewire::actingAs($admin)
            ->test(ManageEntries::class)
            ->assertTableActionVisible('paymentLink', $entry)
            ->mountTableAction('paymentLink', $entry)
            ->assertSchemaStateSet(['link' => route('aanmelden.status', $entry->order)]);
    }

    #[Test]
    public function the_baker_sees_the_summary_accepts_the_terms_and_pays_without_filling_in_anything(): void
    {
        $entry = $this->preparedEntry();
        $order = $entry->order;

        $this->get(route('aanmelden.status', $order))
            ->assertOk()
            ->assertSee('Uw aanmelding staat')
            ->assertSee('Bakkerij Telefoon')
            ->assertSee('Bakkersstraat 12')
            ->assertSee('Filmpje van uw kraam of bakkerij')
            ->assertSee('€ 659,45')
            ->assertSee('name="akkoord_voorwaarden"', false)
            ->assertSee('name="akkoord_privacy"', false);

        // Zonder akkoord geen betaling.
        $this->from(route('aanmelden.status', $order))
            ->post(route('aanmelden.opnieuw', $order))
            ->assertRedirect(route('aanmelden.status', $order))
            ->assertSessionHasErrors(['akkoord_voorwaarden', 'akkoord_privacy']);

        $this->assertNull($entry->fresh()->terms_acceptance_id);
        $this->assertNull($order->fresh()->latestPayment);

        $this->post(route('aanmelden.opnieuw', $order), ['akkoord_voorwaarden' => '1', 'akkoord_privacy' => '1'])
            ->assertRedirect(route('betaling.fake', $order->fresh()->latestPayment));

        $acceptance = TermsAcceptance::query()->sole();

        $this->assertSame($acceptance->getKey(), $entry->fresh()->terms_acceptance_id);
        $this->assertSame($this->terms->getKey(), $acceptance->terms_version_id);
        $this->assertSame($entry->company->owners()->first()->getKey(), $acceptance->participant_user_id);
        $this->assertSame(1, AuditLog::query()->where('action', 'entry.terms_accepted')->count());

        app(MarkOrderPaid::class)($order->fresh());

        $this->assertSame(EntryStatus::Registered, $entry->fresh()->status);
        $this->get(route('aanmelden.status', $order))->assertOk()->assertSee('Betaling ontvangen')->assertDontSee('akkoord_voorwaarden');
    }

    #[Test]
    public function in_the_portal_a_prepared_registration_points_to_the_payment_page(): void
    {
        $entry = $this->preparedEntry();

        $this->actingAs($entry->company->owners()->first(), 'participant')
            ->get(route('portaal.aanmelding', $entry->company))
            ->assertOk()
            ->assertSee('Afronden en betalen')
            ->assertSee(route('aanmelden.status', $entry->order))
            ->assertDontSee('Nu betalen');
    }

    #[Test]
    public function an_unpaid_link_expires_and_gives_the_place_back(): void
    {
        $entry = $this->preparedEntry();

        $this->travel(15)->days();
        app(CancelExpiredReservations::class)();

        $this->assertSame(EntryStatus::Cancelled, $entry->fresh()->status);

        $this->get(route('aanmelden.status', $entry->order))
            ->assertOk()
            ->assertSee('verlopen')
            ->assertDontSee('akkoord_voorwaarden');

        $this->post(route('aanmelden.opnieuw', $entry->order), ['akkoord_voorwaarden' => '1', 'akkoord_privacy' => '1'])
            ->assertRedirect(route('aanmelden.status', $entry->order));

        $this->assertSame(0, TermsAcceptance::query()->count());
    }

    #[Test]
    public function a_baker_who_is_already_registered_is_not_created_twice(): void
    {
        $this->preparedEntry();

        Livewire::actingAs($this->staff(StaffRole::Intake))
            ->test(ManageEntries::class)
            ->callAction('createWithPaymentLink', data: $this->formData(['email' => 'ander@example.test', 'postcode' => '6811 ab']))
            ->assertNotified('Deze bakker is al aangemeld');

        $this->assertSame(1, Entry::query()->count());
    }

    #[Test]
    public function roles_that_do_not_handle_participants_and_money_cannot_create_a_link(): void
    {
        foreach ([StaffRole::Communication, StaffRole::Coordinator, StaffRole::VoucherManager] as $role) {
            Livewire::actingAs($this->staff($role))
                ->test(ManageEntries::class)
                ->assertActionHidden('createWithPaymentLink');
        }

        $entry = $this->preparedEntry();

        Livewire::actingAs($this->staff(StaffRole::Communication))
            ->test(ManageEntries::class)
            ->assertTableActionHidden('paymentLink', $entry);
    }

    #[Test]
    public function a_registration_through_the_website_still_pays_without_extra_checkboxes(): void
    {
        $entry = app(RegisterEntry::class)($this->edition->fresh(), $this->registrationData());

        $this->assertNotNull($entry->terms_acceptance_id);

        $this->get(route('aanmelden.status', $entry->order))->assertOk()->assertDontSee('akkoord_voorwaarden');

        $this->post(route('aanmelden.opnieuw', $entry->order))
            ->assertRedirect(route('betaling.fake', $entry->order->fresh()->latestPayment));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formData(array $overrides = []): array
    {
        return [
            'company_name' => 'Bakkerij Telefoon',
            'company_type' => 'bakkerij',
            'contact_name' => 'Tom Telefoon',
            'email' => 'tom@example.test',
            'phone' => '0612345678',
            'postcode' => '6811 AB',
            'house_number' => '12',
            'street' => 'Bakkersstraat',
            'city' => 'Arnhem',
            'province_id' => Province::query()->where('slug', 'gelderland')->firstOrFail()->getKey(),
            'valid_days' => 14,
            ...$overrides,
        ];
    }

    private function preparedEntry(): Entry
    {
        Livewire::actingAs($this->staff(StaffRole::Admin))
            ->test(ManageEntries::class)
            ->callAction('createWithPaymentLink', data: $this->formData(['video_addon' => true]))
            ->assertHasNoActionErrors();

        auth()->guard('web')->logout();

        return Entry::query()->with(['order', 'company'])->sole();
    }

    private function staff(StaffRole $role): User
    {
        $user = User::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $user->assignRole($role->value);

        return $user;
    }
}
