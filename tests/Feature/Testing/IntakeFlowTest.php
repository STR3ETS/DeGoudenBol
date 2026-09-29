<?php

namespace Tests\Feature\Testing;

use App\Domain\Intake\Actions\AssignSampleNumber;
use App\Domain\Intake\Actions\ReceiveSample;
use App\Domain\Intake\Actions\ScheduleDelivery;
use App\Domain\Intake\Data\IntakeData;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Vault\Exceptions\VaultAccessDeniedException;
use App\Domain\Vault\Models\VaultAccessLog;
use App\Domain\Vault\Models\VaultLink;
use App\Domain\Vault\Services\VaultService;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class IntakeFlowTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    private Entry $entry;

    private User $intake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $this->entry = app(RegisterEntry::class)($this->edition, $this->registrationData());
        $this->entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now()])->save();

        $this->intake = User::factory()->create();
        $this->intake->assignRole(StaffRole::Intake->value);
    }

    #[Test]
    public function a_participant_schedules_a_delivery_slot_and_gets_a_delivery_code(): void
    {
        $slot = DeliverySlot::factory()->create(['edition_id' => $this->edition->getKey(), 'capacity' => 1]);

        $entry = app(ScheduleDelivery::class)($this->entry, $slot);

        $this->assertSame(EntryStatus::Scheduled, $entry->status);
        $this->assertSame($slot->getKey(), $entry->delivery_slot_id);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $entry->delivery_code);
        $this->assertTrue(AuditLog::query()->where('action', 'entry.scheduled')->exists());

        $other = Entry::factory()->create(['edition_id' => $this->edition->getKey(), 'province_id' => $this->entry->province_id]);

        $this->expectException(SlotFullException::class);
        app(ScheduleDelivery::class)($other, $slot);
    }

    #[Test]
    public function receiving_a_sample_creates_it_in_the_test_chain_and_links_it_only_in_the_vault(): void
    {
        $this->actingAs($this->intake);

        $sample = app(ReceiveSample::class)($this->entry, new IntakeData(pieceCount: 8, temperatureC: 22.5), $this->intake);

        $this->assertSame('testing', $sample->getConnectionName());
        $this->assertSame(SampleStatus::Received, $sample->status);
        $this->assertNull($sample->sample_number);
        $this->assertSame(8, $sample->intake->piece_count);
        $this->assertEqualsWithDelta(now()->addMinutes(180)->timestamp, $sample->intake->freshness_expires_at->timestamp, 5);
        $this->assertSame(EntryStatus::Received, $this->entry->refresh()->status);

        $link = VaultLink::query()->where('sample_id', $sample->getKey())->firstOrFail();
        $this->assertStringNotContainsString($this->entry->ulid, $link->entry_ref);
        $this->assertSame($this->entry->ulid, app(VaultService::class)->entryUlidForSample($sample->getKey(), 'test'));
        $this->assertSame($sample->getKey(), app(VaultService::class)->sampleIdForEntry($this->entry->ulid, 'test'));

        $this->assertSame(3, VaultAccessLog::query()->count());
        $this->assertSame($this->intake->email, VaultAccessLog::query()->first()->actor);

        // De auditlog bevat het monster en de inschrijving nooit in dezelfde regel.
        $this->assertTrue(AuditLog::query()->where('action', 'entry.received')->exists());
        $sampleLog = AuditLog::query()->where('action', 'sample.received')->firstOrFail();
        $this->assertNull($sampleLog->subject_id);
        $this->assertStringNotContainsString($this->entry->ulid, json_encode($sampleLog->payload));
    }

    #[Test]
    public function sample_numbers_count_up_per_edition_and_round(): void
    {
        $this->actingAs($this->intake);

        $first = app(ReceiveSample::class)($this->entry, new IntakeData(pieceCount: 8), $this->intake);
        $first = app(AssignSampleNumber::class)($first, $this->intake);

        $second = Sample::factory()->create(['edition_id' => $this->edition->getKey()]);
        $second = app(AssignSampleNumber::class)($second, $this->intake);

        $this->assertSame('0001', $first->sample_number);
        $this->assertSame('0002', $second->sample_number);
        $this->assertSame(SampleStatus::Numbered, $first->status);
        $this->assertSame(EntryStatus::Numbered, $this->entry->refresh()->status);
    }

    #[Test]
    public function only_intake_and_publication_roles_may_open_the_vault(): void
    {
        $reviewer = User::factory()->create();
        $reviewer->assignRole(StaffRole::Reviewer->value);
        $this->actingAs($reviewer);

        $this->expectException(VaultAccessDeniedException::class);
        app(VaultService::class)->sampleIdForEntry($this->entry->ulid, 'poging');
    }

    #[Test]
    public function the_administrator_has_no_vault_access_either(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(StaffRole::Admin->value);
        $this->actingAs($admin);

        $this->expectException(VaultAccessDeniedException::class);
        app(VaultService::class)->entryUlidForSample(1, 'poging');
    }
}
