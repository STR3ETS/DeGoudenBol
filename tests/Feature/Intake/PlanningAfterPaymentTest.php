<?php

namespace Tests\Feature\Intake;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Intake\Notifications\DeliveryScheduledNotification;
use App\Domain\Intake\Notifications\PlanDeliveryReminderNotification;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Platform\Enums\StaffRole;
use App\Filament\Support\WorkQueue;
use App\Models\User;
use App\Support\DutchTime;
use Carbon\CarbonInterface;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

/**
 * Na de betaling direct een aanlevermoment kiezen op de bedankpagina, en een dagelijkse herinnering voor
 * wie dat nog niet deed (besluit Raphael 2 nov 2026).
 */
class PlanningAfterPaymentTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();
    }

    #[Test]
    public function after_paying_the_baker_chooses_a_delivery_moment_without_logging_in(): void
    {
        Notification::fake();

        $slot = DeliverySlot::factory()->create(['edition_id' => $this->edition->getKey(), 'capacity' => 4, 'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHours(2)]);
        $entry = $this->paidEntry();

        $this->get(route('aanmelden.status', $entry->order))
            ->assertOk()
            ->assertSee('Kies uw')
            ->assertSee('aanlevermoment')
            ->assertSee(ucfirst($slot->label()));

        $this->post(route('aanmelden.moment', $entry->order), ['slot' => $slot->getKey()])
            ->assertRedirect(route('aanmelden.status', $entry->order));

        $entry->refresh();
        $this->assertSame(EntryStatus::Scheduled, $entry->status);
        $this->assertSame($slot->getKey(), $entry->delivery_slot_id);
        Notification::assertSentTo($this->owner($entry), DeliveryScheduledNotification::class);

        $this->get(route('aanmelden.status', $entry->order))->assertOk()->assertSee('Uw aanlevermoment')->assertSee($entry->delivery_code)->assertDontSee('Aanlevermoment vastleggen');
    }

    #[Test]
    public function a_prepaid_pickup_chooses_the_friday_on_the_same_page(): void
    {
        Notification::fake();

        $friday = PickupDay::query()->create([
            'edition_id' => $this->edition->getKey(),
            'date' => DutchTime::display(now())->next(CarbonInterface::FRIDAY)->addWeek()->toDateString(),
            'capacity' => 3,
        ]);
        $entry = $this->paidEntry(['pickup' => true]);

        $this->get(route('aanmelden.status', $entry->order))->assertOk()->assertSee('ophaalvrijdag')->assertSee($friday->label());

        $this->post(route('aanmelden.ophalen', $entry->order), [
            'pickup_day' => $friday->getKey(),
            'contact_name' => 'Tessa Tester',
            'phone' => '0612345678',
            'street' => 'Bakkersstraat',
            'house_number' => '12a',
            'postcode' => '6811 AB',
            'city' => 'Arnhem',
        ])->assertRedirect(route('aanmelden.status', $entry->order));

        $pickup = $entry->pickupRequest()->firstOrFail();
        $this->assertSame(PickupStatus::Paid, $pickup->status);
        $this->assertSame(EntryStatus::Scheduled, $entry->fresh()->status);
    }

    #[Test]
    public function an_unpaid_order_cannot_plan(): void
    {
        $slot = DeliverySlot::factory()->create(['edition_id' => $this->edition->getKey(), 'capacity' => 4, 'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHours(2)]);
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData());

        $this->post(route('aanmelden.moment', $entry->order), ['slot' => $slot->getKey()])->assertNotFound();
        $this->assertNull($entry->fresh()->delivery_slot_id);
    }

    #[Test]
    public function the_daily_reminder_goes_out_once_a_day_and_the_dashboard_follows_up_after_two(): void
    {
        Notification::fake();

        DeliverySlot::factory()->create(['edition_id' => $this->edition->getKey(), 'capacity' => 4, 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHours(2)]);
        $entry = $this->paidEntry();
        $owner = $this->owner($entry);

        // Net betaald: eerst een dag de tijd.
        $this->artisan('deliveries:plan-reminder')->assertSuccessful();
        Notification::assertNotSentTo($owner, PlanDeliveryReminderNotification::class);

        $this->travel(1)->days();
        $this->artisan('deliveries:plan-reminder')->assertSuccessful();
        $this->artisan('deliveries:plan-reminder')->assertSuccessful();
        Notification::assertSentToTimes($owner, PlanDeliveryReminderNotification::class, 1);
        Notification::assertSentTo($owner, PlanDeliveryReminderNotification::class, fn (PlanDeliveryReminderNotification $mail) => $mail->entry->is($entry) && $mail->toMail($owner)->actionUrl === route('aanmelden.status', $entry->order));

        $admin = User::factory()->create();
        $admin->assignRole(StaffRole::Admin->value);
        $this->actingAs($admin);
        $this->assertNotContains('Nog geen aanlevermoment', $this->workQueueTitles($admin));

        $this->travel(1)->days();
        $this->artisan('deliveries:plan-reminder')->assertSuccessful();
        Notification::assertSentToTimes($owner, PlanDeliveryReminderNotification::class, 2);
        $this->assertContains('Nog geen aanlevermoment', $this->workQueueTitles($admin));

        $this->travel(1)->days();
        $this->artisan('deliveries:plan-reminder')->assertSuccessful();
        $this->travel(1)->days();
        $this->artisan('deliveries:plan-reminder')->assertSuccessful();
        Notification::assertSentToTimes($owner, PlanDeliveryReminderNotification::class, 3);
        $this->assertSame(3, $entry->fresh()->planning_reminders);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function paidEntry(array $overrides = []): Entry
    {
        $entry = app(RegisterEntry::class)($this->edition, $this->registrationData($overrides));
        app(MarkOrderPaid::class)($entry->order);

        return $entry->fresh(['order']);
    }

    private function owner(Entry $entry): ParticipantUser
    {
        return $entry->company->owners()->firstOrFail();
    }

    /**
     * @return list<string>
     */
    private function workQueueTitles(User $user): array
    {
        return array_column((new WorkQueue($user, $this->edition->fresh(), now()->toImmutable()))->items(), 'title');
    }
}
