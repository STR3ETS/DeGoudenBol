<?php

namespace Tests\Feature\Intake;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Gateways\FakePaymentGateway;
use App\Domain\Intake\Actions\RequestPickup;
use App\Domain\Intake\Actions\UpdatePickupStatus;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Events\EntryReceived;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Intake\Notifications\EntryReceivedNotification;
use App\Domain\Intake\Notifications\PickupConfirmedNotification;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\DeliveryMethod;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Filament\Resources\PickupRequests\PickupRequestResource;
use App\Support\DutchTime;
use Carbon\CarbonInterface;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Ophaalservice uit de briefing van 29 okt (§5): alleen vrijdag, capaciteit, pas actief na betaling.
 */
class PickupServiceTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $entry;

    private ParticipantUser $owner;

    private PickupDay $friday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $this->entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();
        $this->owner = $this->entry->company->users()->wherePivot('role', CompanyUserRole::Owner->value)->firstOrFail();

        $this->friday = PickupDay::query()->create([
            'edition_id' => $this->edition->getKey(),
            'date' => DutchTime::display(now())->next(CarbonInterface::FRIDAY)->addWeek()->toDateString(),
            'capacity' => 1,
        ]);
    }

    #[Test]
    public function a_baker_requests_a_friday_pickup_pays_the_fee_and_is_scheduled(): void
    {
        Notification::fake();

        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.planning', $this->entry->company))
            ->assertOk()
            ->assertSee('Laten ophalen')
            ->assertSee('€ 45,00');

        $response = $this->actingAs($this->owner, 'participant')->post(route('portaal.ophalen', $this->entry->company), $this->pickupForm());
        $response->assertRedirect();

        $pickup = $this->entry->pickupRequest()->firstOrFail();
        $this->assertSame(PickupStatus::Requested, $pickup->status);
        $this->assertSame(5445, $pickup->order->total_cents); // EUR 45 + 21% btw
        $this->assertSame(EntryStatus::Registered, $this->entry->fresh()->status);

        $payment = $pickup->order->latestPayment;
        $this->post(route('betaling.fake.afronden', $payment), ['status' => 'paid'])->assertRedirect(route('portaal.ophalen.terug', $pickup->order));

        $pickup->refresh();
        $entry = $this->entry->fresh();
        $this->assertSame(PickupStatus::Paid, $pickup->status);
        $this->assertSame(EntryStatus::Scheduled, $entry->status);
        $this->assertSame(DeliveryMethod::Pickup, $entry->delivery_method);
        $this->assertNotNull($entry->delivery_code);
        $this->assertSame('Bakkerij Testers', $pickup->order->invoice->billing_name);
        Notification::assertSentToTimes($this->owner, PickupConfirmedNotification::class, 1);

        $this->actingAs($this->owner, 'participant')->get(route('portaal.planning.bewijs', $this->entry->company))->assertOk()->assertSee('Ophaalbewijs')->assertSee($entry->delivery_code);
    }

    #[Test]
    public function only_open_fridays_with_room_can_be_booked(): void
    {
        $thursday = PickupDay::query()->create(['edition_id' => $this->edition->getKey(), 'date' => $this->friday->date->subDay()->toDateString(), 'capacity' => 5]);

        try {
            app(RequestPickup::class)($this->entry, $thursday, $this->pickupForm(), $this->owner);
            $this->fail('Een donderdag hoort niet te kunnen.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('vrijdag', $exception->getMessage());
        }

        $other = app(RegisterEntry::class)($this->edition, $this->registrationData(['companyName' => 'Bakkerij Twee', 'publicName' => 'Bakkerij Twee', 'email' => 'twee@example.test', 'kvkNumber' => '22222222']));
        $other->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();

        app(RequestPickup::class)($this->entry, $this->friday, $this->pickupForm(), $this->owner);

        $this->expectException(SlotFullException::class);
        app(RequestPickup::class)($other, $this->friday, $this->pickupForm(), $other->company->users()->firstOrFail());
    }

    #[Test]
    public function scheduling_and_receiving_each_send_one_mail_and_the_route_can_be_exported(): void
    {
        Notification::fake();

        $pickup = app(RequestPickup::class)($this->entry, $this->friday, $this->pickupForm(), $this->owner);
        app(FakePaymentGateway::class)->create($pickup->order, 'Test', route('portaal.ophalen.terug', $pickup->order));
        app(MarkOrderPaid::class)($pickup->order);

        $update = app(UpdatePickupStatus::class);
        $update($pickup->refresh(), PickupStatus::Scheduled, null, 'Gill', 1);
        $update($pickup->refresh(), PickupStatus::Scheduled, null, 'Gill', 1);

        Notification::assertSentToTimes($this->owner, PickupConfirmedNotification::class, 2); // bevestigd + ingepland

        EntryReceived::dispatch($this->entry->fresh());
        EntryReceived::dispatch($this->entry->fresh());

        $this->assertSame(PickupStatus::Received, $pickup->refresh()->status);
        Notification::assertSentToTimes($this->owner, EntryReceivedNotification::class, 1);

        $csv = PickupRequestResource::export($this->friday);
        ob_start();
        $csv->sendContent();
        $content = (string) ob_get_clean();

        $this->assertStringContainsString('Bakkersstraat', $content);
        $this->assertStringContainsString('Gill', $content);
    }

    /**
     * @return array<string, string>
     */
    private function pickupForm(): array
    {
        return [
            'pickup_day' => (string) $this->friday->getKey(),
            'company_name' => 'Bakkerij Testers',
            'contact_name' => 'Tessa Tester',
            'phone' => '0612345678',
            'street' => 'Bakkersstraat',
            'house_number' => '12a',
            'postcode' => '6811 AB',
            'city' => 'Arnhem',
            'instructions' => 'Achterom',
        ];
    }
}
