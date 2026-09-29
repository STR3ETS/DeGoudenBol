<?php

namespace Tests\Feature\Commerce;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Enums\InvoiceStatus;
use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Enums\PaymentStatus;
use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Commerce\Gateways\FakePaymentGateway;
use App\Domain\Commerce\Models\Invoice;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Events\EntryRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class MarkOrderPaidTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();
    }

    #[Test]
    public function paying_an_order_confirms_the_entry_and_issues_a_sequential_invoice(): void
    {
        Event::fake([OrderPaid::class, EntryRegistered::class]);

        $first = app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'a@example.test', 'companyName' => 'Bakker A']));
        $second = app(RegisterEntry::class)($this->edition, $this->registrationData(['email' => 'b@example.test', 'companyName' => 'Bakker B']));

        app(MarkOrderPaid::class)($first->order);
        app(MarkOrderPaid::class)($second->order);

        $first->refresh();
        $this->assertSame(EntryStatus::Registered, $first->status);
        $this->assertNull($first->reservation_expires_at);
        $this->assertNotNull($first->confirmed_at);

        $order = $first->order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(InvoiceStatus::Paid, $order->invoice->status);
        $this->assertSame(now()->year.'-0001', $order->invoice->number);
        $this->assertSame(now()->year.'-0002', $second->order->refresh()->invoice->number);
        $this->assertSame('Bakker A', $order->invoice->billing_name);
        $this->assertSame('Arnhem', $order->invoice->billing_address['city']);

        Event::assertDispatchedTimes(OrderPaid::class, 2);
        Event::assertDispatchedTimes(EntryRegistered::class, 2);
    }

    #[Test]
    public function marking_paid_twice_is_idempotent(): void
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());

        app(MarkOrderPaid::class)($entry->order);
        app(MarkOrderPaid::class)($entry->order);

        $this->assertSame(1, Invoice::query()->count());
    }

    #[Test]
    public function the_fake_checkout_settles_the_payment_and_pays_the_order(): void
    {
        config()->set('commerce.payment_driver', 'fake');

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $payment = app(FakePaymentGateway::class)->create($entry->order, 'Test', 'https://degoudenbol.test/klaar');

        $this->get(route('betaling.fake', $payment))->assertOk()->assertSee($entry->order->number);

        $this->post(route('betaling.fake.afronden', $payment), ['status' => 'paid'])
            ->assertRedirect('https://degoudenbol.test/klaar');

        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertSame(EntryStatus::Registered, $entry->refresh()->status);
    }

    #[Test]
    public function the_fake_checkout_does_not_exist_with_mollie(): void
    {
        config()->set('commerce.payment_driver', 'mollie');

        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $payment = app(FakePaymentGateway::class)->create($entry->order, 'Test', 'https://degoudenbol.test/klaar');

        $this->get(route('betaling.fake', $payment))->assertNotFound();
    }
}
