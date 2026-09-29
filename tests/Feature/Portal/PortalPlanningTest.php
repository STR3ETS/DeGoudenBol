<?php

namespace Tests\Feature\Portal;

use App\Domain\Intake\Notifications\DeliveryScheduledNotification;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
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
            ->assertSee('Kies hieronder een tijdslot')
            ->assertSee('4 plekken vrij')
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
    }

    #[Test]
    public function the_proof_is_not_available_without_a_slot(): void
    {
        $this->actingAs($this->owner, 'participant')
            ->get(route('portaal.planning.bewijs', $this->entry->company))
            ->assertNotFound();
    }
}
