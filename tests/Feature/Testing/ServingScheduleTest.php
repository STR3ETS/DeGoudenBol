<?php

namespace Tests\Feature\Testing;

use App\Domain\Intake\Actions\AssignSampleNumber;
use App\Domain\Intake\Actions\ReceiveSample;
use App\Domain\Intake\Data\IntakeData;
use App\Domain\Participants\Actions\RegisterEntry;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Models\PanelistConflict;
use App\Domain\Testing\Actions\GenerateServingSchedule;
use App\Domain\Testing\Enums\ExclusionReason;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Jobs\GenerateServingScheduleJob;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\TestSession;
use App\Domain\Vault\Models\VaultAccessLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsRegistrations;
use Tests\TestCase;

class ServingScheduleTest extends TestCase
{
    use BuildsRegistrations, RefreshDatabase;

    /** @var list<Entry> */
    private array $entries = [];

    /** @var list<Sample> */
    private array $samples = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->setUpRegistrationWorld();

        $intake = User::factory()->create();
        $intake->assignRole(StaffRole::Intake->value);
        $this->actingAs($intake);

        foreach ([
            ['Bakkerij Noten', 'noten@example.test', ['gluten', 'noten']],
            ['Bakkerij Twee', 'twee@example.test', ['gluten']],
            ['Bakkerij Drie', 'drie@example.test', ['gluten', 'melk']],
        ] as [$name, $email, $allergens]) {
            $entry = app(RegisterEntry::class)($this->edition, $this->registrationData(['companyName' => $name, 'publicName' => $name, 'email' => $email, 'kvkNumber' => (string) random_int(10000000, 99999999)]));
            $entry->forceFill(['status' => EntryStatus::Registered, 'confirmed_at' => now(), 'allergens' => $allergens])->save();

            $sample = app(ReceiveSample::class)($entry, new IntakeData(pieceCount: 8), $intake);
            $this->samples[] = app(AssignSampleNumber::class)($sample, $intake);
            $this->entries[] = $entry;
        }

        auth()->logout();
    }

    #[Test]
    public function the_schedule_rotates_samples_and_excludes_conflicts_and_allergens_without_names(): void
    {
        $session = TestSession::factory()->create(['edition_id' => $this->edition->getKey()]);
        $session->samples()->attach(collect($this->samples)->mapWithKeys(fn (Sample $sample, int $i) => [$sample->getKey() => ['serving_order' => $i + 1]])->all());

        $plain = Panelist::factory()->create();
        $allergic = Panelist::factory()->allergicTo(['noten'])->create();
        $conflicted = Panelist::factory()->create();
        PanelistConflict::query()->create(['user_id' => $conflicted->user_id, 'company_id' => $this->entries[1]->company_id]);

        $session->panelists()->attach([$plain->getKey(), $allergic->getKey(), $conflicted->getKey()]);

        $coordinator = User::factory()->create();
        $coordinator->assignRole(StaffRole::Coordinator->value);
        $this->actingAs($coordinator);

        GenerateServingScheduleJob::dispatchSync($session->getKey());

        $session->refresh();
        $this->assertNotNull($session->schedule_generated_at);
        $this->assertSame(3, $session->assignments()->where('panelist_id', $plain->getKey())->count());
        $this->assertSame(2, $session->assignments()->where('panelist_id', $allergic->getKey())->count());
        $this->assertSame(2, $session->assignments()->where('panelist_id', $conflicted->getKey())->count());

        $allergenExclusion = $session->exclusions()->where('panelist_id', $allergic->getKey())->firstOrFail();
        $this->assertSame($this->samples[0]->getKey(), $allergenExclusion->sample_id);
        $this->assertSame(ExclusionReason::Allergen, $allergenExclusion->reason_code);

        $conflictExclusion = $session->exclusions()->where('panelist_id', $conflicted->getKey())->firstOrFail();
        $this->assertSame($this->samples[1]->getKey(), $conflictExclusion->sample_id);
        $this->assertSame(ExclusionReason::Conflict, $conflictExclusion->reason_code);

        // Rotatie: het tweede panellid begint bij het tweede monster.
        $firstOfPlain = $session->assignments()->where('panelist_id', $plain->getKey())->where('serving_order', 1)->value('sample_id');
        $firstOfAllergic = $session->assignments()->where('panelist_id', $allergic->getKey())->where('serving_order', 1)->value('sample_id');
        $this->assertSame($this->samples[0]->getKey(), $firstOfPlain);
        $this->assertSame($this->samples[1]->getKey(), $firstOfAllergic);

        $this->assertSame(SampleStatus::Scheduled, $this->samples[0]->refresh()->status);

        // De kluis is als systeem geraadpleegd, niet als de coördinator.
        $this->assertTrue(VaultAccessLog::query()->where('actor', 'system')->where('reason', 'like', 'uitserveerschema%')->exists());
        $this->assertFalse(VaultAccessLog::query()->where('actor', $coordinator->email)->exists());
    }

    #[Test]
    public function a_session_without_samples_or_panelists_cannot_be_scheduled(): void
    {
        $session = TestSession::factory()->create(['edition_id' => $this->edition->getKey()]);

        $this->expectExceptionMessage('Koppel eerst monsters en panelleden');
        app(GenerateServingSchedule::class)($session);
    }
}
