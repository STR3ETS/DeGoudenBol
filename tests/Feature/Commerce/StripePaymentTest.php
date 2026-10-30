<?php

namespace Tests\Feature\Commerce;

use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Enums\PaymentStatus;
use App\Domain\Commerce\Gateways\StripePaymentGateway;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private const string SESSION = 'cs_test_a1B2c3D4e5F6g7H8i9J0k1L2m3N4o5P6q7R8s9T0u1V2w3X4y5Z6a7B8c9D0e1F2';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
        config()->set('commerce.payment_driver', 'stripe');
        config()->set('services.stripe.secret', 'sk_test_geheim');
        config()->set('services.stripe.webhook_secret', 'whsec_test');
    }

    #[Test]
    public function the_driver_creates_a_checkout_session_and_stores_its_url(): void
    {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => self::SESSION, 'url' => 'https://checkout.stripe.com/c/pay/'.self::SESSION, 'status' => 'open', 'payment_status' => 'unpaid'])]);

        $this->assertInstanceOf(StripePaymentGateway::class, app(PaymentGateway::class));

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $payment = app(PaymentGateway::class)->create($entry->order, 'De Gouden Bol 2026 – deelname Bakkerij Testers', route('aanmelden.status', $entry->order));

        $this->assertSame('stripe', $payment->provider);
        $this->assertSame(self::SESSION, $payment->provider_id);
        $this->assertSame(PaymentStatus::Open, $payment->status);
        $this->assertStringStartsWith('https://checkout.stripe.com/', $payment->checkout_url);

        Http::assertSent(function (Request $request) use ($entry): bool {
            return $request->hasHeader('Authorization', 'Bearer sk_test_geheim')
                && $request['mode'] === 'payment'
                && $request['line_items'][0]['price_data']['unit_amount'] === $entry->order->total_cents
                && $request['line_items'][0]['price_data']['currency'] === 'eur'
                && $request['success_url'] === route('aanmelden.status', $entry->order)
                && $request['customer_email'] === 'tessa@example.test';
        });
    }

    #[Test]
    public function a_signed_webhook_refreshes_the_session_and_pays_the_order_once(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $entry->order->payments()->create(['provider' => 'stripe', 'provider_id' => self::SESSION, 'status' => PaymentStatus::Open, 'amount_cents' => $entry->order->total_cents]);

        Http::fake(['api.stripe.com/v1/checkout/sessions/*' => Http::response([
            'id' => self::SESSION,
            'status' => 'complete',
            'payment_status' => 'paid',
            'payment_intent' => ['latest_charge' => ['created' => now()->getTimestamp(), 'payment_method_details' => ['type' => 'ideal']]],
        ])]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['id' => self::SESSION, 'payment_status' => 'paid']]]);

        $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $this->sign($payload), 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
        $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $this->sign($payload), 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();

        $entry->refresh();
        $this->assertSame(EntryStatus::Registered, $entry->status);
        $this->assertTrue($entry->order->isPaid());
        $this->assertSame('ideal', $entry->order->latestPayment->method);
        $this->assertSame(1, $entry->order->invoice()->count());
    }

    #[Test]
    public function an_unsigned_or_forged_webhook_is_refused_and_changes_nothing(): void
    {
        Http::fake();
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $entry->order->payments()->create(['provider' => 'stripe', 'provider_id' => self::SESSION, 'status' => PaymentStatus::Open, 'amount_cents' => $entry->order->total_cents]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['id' => self::SESSION]]]);

        $this->call('POST', route('webhooks.stripe'), [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
        $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=vervalst', 'CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);

        Http::assertNothingSent();
        $this->assertFalse($entry->order->refresh()->isPaid());
    }

    #[Test]
    public function the_signature_check_rejects_old_events(): void
    {
        $payload = '{"type":"checkout.session.completed"}';
        $old = time() - 3600;
        $header = 't='.$old.',v1='.hash_hmac('sha256', $old.'.'.$payload, 'whsec_test');

        $this->assertFalse(StripeWebhookController::signatureIsValid($payload, $header, 'whsec_test'));
        $this->assertTrue(StripeWebhookController::signatureIsValid($payload, $header, 'whsec_test', $old + 10));
        $this->assertFalse(StripeWebhookController::signatureIsValid($payload, $header, ''));
    }

    #[Test]
    public function the_session_status_maps_to_our_payment_status(): void
    {
        $this->assertSame(PaymentStatus::Open, StripePaymentGateway::statusFor('open', 'unpaid'));
        $this->assertSame(PaymentStatus::Paid, StripePaymentGateway::statusFor('complete', 'paid'));
        $this->assertSame(PaymentStatus::Pending, StripePaymentGateway::statusFor('complete', 'unpaid'));
        $this->assertSame(PaymentStatus::Expired, StripePaymentGateway::statusFor('expired', 'unpaid'));
    }

    #[Test]
    public function returning_to_the_status_page_checks_stripe_and_confirms_the_registration(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $entry->order->payments()->create(['provider' => 'stripe', 'provider_id' => self::SESSION, 'status' => PaymentStatus::Open, 'amount_cents' => $entry->order->total_cents]);

        Http::fake(['api.stripe.com/v1/checkout/sessions/*' => Http::response(['id' => self::SESSION, 'status' => 'complete', 'payment_status' => 'paid', 'payment_intent' => null])]);

        $this->get(route('aanmelden.status', $entry->order))->assertOk();

        $this->assertTrue($entry->order->refresh()->isPaid());
    }

    private function sign(string $payload): string
    {
        $time = time();

        return 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$payload, 'whsec_test');
    }
}
