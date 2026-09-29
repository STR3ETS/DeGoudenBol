<?php

namespace Tests\Feature\Participants;

use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Enums\PaymentStatus;
use App\Domain\Commerce\Gateways\FakePaymentGateway;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Payment;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Notifications\EntryRegisteredNotification;
use App\Domain\Participants\Services\MagicLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class RegistrationStatusAndWebhookTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
    }

    #[Test]
    public function the_status_page_confirms_a_paid_order_and_sends_the_confirmation_mail(): void
    {
        Notification::fake();

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $payment = app(FakePaymentGateway::class)->create($entry->order, 'Test', route('aanmelden.status', $entry->order));

        $this->post(route('betaling.fake.afronden', $payment), ['status' => 'paid'])
            ->assertRedirect(route('aanmelden.status', $entry->order));

        $this->get(route('aanmelden.status', $entry->order))
            ->assertOk()
            ->assertSee('Betaling ontvangen')
            ->assertSee('Bakkerij Testers');

        Notification::assertSentTo($entry->company->users()->first(), EntryRegisteredNotification::class);
    }

    #[Test]
    public function a_failed_payment_offers_a_retry_while_the_reservation_lasts(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $payment = app(FakePaymentGateway::class)->create($entry->order, 'Test', route('aanmelden.status', $entry->order));
        app(FakePaymentGateway::class)->settle($payment, PaymentStatus::Failed);

        $this->get(route('aanmelden.status', $entry->order))
            ->assertOk()
            ->assertSee('niet gelukt')
            ->assertSee('Opnieuw betalen');

        $this->post(route('aanmelden.opnieuw', $entry->order))->assertRedirect();

        $this->assertSame(2, Payment::query()->where('order_id', $entry->order->getKey())->count());
    }

    #[Test]
    public function an_expired_reservation_cannot_be_retried(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $entry->forceFill(['reservation_expires_at' => now()->subMinute()])->save();

        $this->get(route('aanmelden.status', $entry->order))
            ->assertOk()
            ->assertSee('verlopen')
            ->assertDontSee('Opnieuw betalen');
    }

    #[Test]
    public function the_mollie_webhook_refreshes_the_payment_and_pays_the_order(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $payment = $entry->order->payments()->create([
            'provider' => 'mollie',
            'provider_id' => 'tr_test123',
            'status' => PaymentStatus::Open,
            'amount_cents' => $entry->order->total_cents,
        ]);

        $this->app->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
        {
            public function create(Order $order, string $description, string $redirectUrl, ?string $webhookUrl = null): Payment
            {
                throw new \LogicException('niet nodig');
            }

            public function refresh(Payment $payment): Payment
            {
                $payment->forceFill(['status' => PaymentStatus::Paid, 'method' => 'ideal', 'paid_at' => now()])->save();

                return $payment;
            }

            public function name(): string
            {
                return 'mollie';
            }
        });

        $this->post(route('webhooks.mollie'), ['id' => 'tr_test123'])->assertOk();
        $this->post(route('webhooks.mollie'), ['id' => 'tr_onbekend'])->assertOk();

        $this->assertSame(EntryStatus::Registered, $entry->refresh()->status);
        $this->assertTrue($entry->order->refresh()->isPaid());
    }

    #[Test]
    public function a_magic_link_logs_the_participant_in_once(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $user = $entry->company->users()->first();

        $url = app(MagicLinkService::class)->url($user);

        $this->get($url)->assertRedirect(route('portaal.dashboard'));
        $this->assertAuthenticatedAs($user, 'participant');
        $this->assertNotNull($user->refresh()->last_login_at);

        auth('participant')->logout();

        $this->get($url)->assertRedirect(route('portaal.inloggen'));
        $this->assertGuest('participant');
    }
}
