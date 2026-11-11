<?php

namespace Tests\Feature\Portal;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Intake\Notifications\DeliveryScheduledNotification;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class PortalPlanningTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $entry;

    private ParticipantUser $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegistrationWorld();

        $this->entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $this->entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();
        $this->owner = $this->entry->company->users()->firstOrFail();
    }

    #[Test]
    public function a_participant_sees_the_slots_chooses_one_and_gets_a_qr_delivery_proof(): void
    {
        Notification::fake();

        $slot = DeliverySlot::factory()->create(['edition_id' => $this->edition->getKey(), 'capacity' => 4]);
        $full = DeliverySlot::factory()->create(['edition_id' => $this->edition->getKey(), 'capacity' => 0]);

        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.planning'))
            ->assertOk()
            ->assertSee('Zelf brengen')
            ->assertDontSee('4 vrij')
            ->assertSee('Vol');

        $this->actingAs($this->owner, 'participant')
            ->post(route('portaal.planning.kiezen', $this->entry->company), ['slot' => $full->getKey()])
            ->assertSessionHasErrors('slot');

        $this->actingAs($this->owner, 'participant')
            ->post(route('portaal.planning.kiezen', $this->entry->company), ['slot' => $slot->getKey()])
            ->assertRedirect(route('portaal.planning', $this->entry->company));

        $this->entry->refresh();
        $this->assertSame(EntryStatus::Scheduled, $this->entry->status);
        $this->assertSame($slot->getKey(), $this->entry->delivery_slot_id);
        $this->assertNotNull($this->entry->delivery_code);

        Notification::assertSentTo($this->owner, DeliveryScheduledNotification::class);

        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.planning.bewijs', $this->entry->company))
            ->assertOk()
            ->assertSee($this->entry->delivery_code)
            ->assertSee('<svg', false)
            ->assertSee('Aanleverbewijs');

        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.dashboard'))
            ->assertOk()
            ->assertSee('aanleverbewijs');

        // Verzoek Gill 10 nov 2026: "Ander moment kiezen" staat niet meer bovenaan maar als eerste tegel bij "Ook handig".
        $dashboard = $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.dashboard'))
            ->assertSeeInOrder(['Open je aanleverbewijs', 'Ook handig', 'Ander moment kiezen', 'Vertel iets over je zaak', 'Oorkonde', 'Geef een collega toegang']);

        $this->assertSame(1, substr_count($dashboard->getContent(), 'Ander moment kiezen'));
    }

    #[Test]
    public function the_top_10_partner_package_can_be_requested_from_the_portal(): void
    {
        $this->seed(ProductSeeder::class);

        app(MarkOrderPaid::class)($this->entry->order);

        $request = route('sponsoring.aanmelden', ['pakket' => 'national_top']);

        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.aanmelding', $this->entry->company))
            ->assertOk()
            ->assertSee('Top 10 partner')
            ->assertSee('€ 450,00')
            ->assertSee('Elke extra vermelding € 75,00')
            ->assertSee($request);

        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.sponsoren', $this->entry->company))
            ->assertOk()
            ->assertSee('Top 10 partner')
            ->assertSee($request);

        $this->get($request)->assertOk();
    }

    #[Test]
    public function the_proof_is_not_available_without_a_slot(): void
    {
        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.planning.bewijs', $this->entry->company))
            ->assertNotFound();
    }
}
