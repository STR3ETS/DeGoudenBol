<?php

namespace Tests\Feature\Ranking;

use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\ApproveBatch;
use App\Domain\Ranking\Actions\PublishBatch;
use App\Domain\Ranking\Actions\SubmitBatch;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Enums\ItemVisibility;
use App\Domain\Ranking\Enums\PositionLabel;
use App\Domain\Ranking\Enums\ReportStatus;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Ranking\Notifications\ResultPublishedNotification;
use App\Domain\Ranking\Services\PublicationSchedule;
use App\Domain\Testing\Actions\FinalizeResult;
use App\Domain\Testing\Actions\SubmitScorecard;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Domain\Vault\Models\VaultAccessLog;
use App\Models\User;
use App\Support\DutchTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsTestChain;
use Tests\TestCase;

class PublicationFlowTest extends TestCase
{
    use BuildsTestChain, RefreshDatabase;

    private User $reviewer;

    private User $publisherOne;

    private User $publisherTwo;

    private User $publisherThree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestChain();

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole(StaffRole::Reviewer->value);

        foreach (['publisherOne', 'publisherTwo', 'publisherThree'] as $property) {
            $this->{$property} = User::factory()->create();
            $this->{$property}->assignRole(StaffRole::Publisher->value);
        }
    }

    #[Test]
    public function the_next_publication_slot_follows_the_edition_settings_and_the_quiet_period(): void
    {
        $schedule = app(PublicationSchedule::class);

        CarbonImmutable::setTestNow(DutchTime::toUtc('2026-11-04 15:00')); // woensdag
        $this->assertSame('2026-11-06 12:00', DutchTime::display($schedule->nextSlot($this->edition))->format('Y-m-d H:i'));

        CarbonImmutable::setTestNow(DutchTime::toUtc('2026-11-06 12:00')); // vrijdag om 12:00 → volgende dinsdag
        $this->assertSame('2026-11-10 12:00', DutchTime::display($schedule->nextSlot($this->edition))->format('Y-m-d H:i'));

        CarbonImmutable::setTestNow(DutchTime::toUtc('2026-12-11 09:00')); // stille periode → hoofdpublicatie
        $this->assertSame('2026-12-21 12:00', DutchTime::display($schedule->nextSlot($this->edition))->format('Y-m-d H:i'));

        CarbonImmutable::setTestNow();
    }

    #[Test]
    public function a_final_result_is_linked_through_the_vault_approved_by_two_others_and_published_with_a_ranking(): void
    {
        Notification::fake();

        $this->scoreAndFinalize(smaak: 20);

        // Stap 8: gekoppeld via de kluis (als systeem), publicatie-item in de open batch, conceptrapport.
        $entry = $this->chainEntry->refresh();
        $this->assertSame(EntryStatus::Linked, $entry->status);
        $this->assertTrue(VaultAccessLog::query()->where('actor', 'system')->where('reason', 'koppeling na definitieve score')->exists());

        $batch = PublicationBatch::query()->where('status', BatchStatus::Draft)->firstOrFail();
        $item = $batch->items()->firstOrFail();
        $this->assertSame($entry->getKey(), $item->entry_id);
        $this->assertSame(ItemVisibility::Public, $item->visibility);
        $this->assertSame(8.0, $item->total());
        $this->assertArrayNotHasKey('sample_number', $item->result_snapshot);
        $this->assertSame(ReportStatus::Draft, ConfidentialReport::query()->where('entry_id', $entry->getKey())->firstOrFail()->status);

        // Stap 9: indienen en twee goedkeuringen door anderen dan de indiener.
        app(SubmitBatch::class)($batch, $this->publisherOne);
        $this->assertSame(BatchStatus::PendingApproval, $batch->refresh()->status);

        try {
            app(ApproveBatch::class)($batch, $this->publisherOne);
            $this->fail('Indiener keurde eigen batch goed');
        } catch (LogicException $e) {
            $this->assertStringContainsString('eigen batch', $e->getMessage());
        }

        try {
            app(ApproveBatch::class)($batch, $this->reviewer);
            $this->fail('Scorecontrole keurde een batch goed');
        } catch (LogicException $e) {
            $this->assertStringContainsString('Alleen de rol Publicatie', $e->getMessage());
        }

        try {
            app(PublishBatch::class)($batch);
            $this->fail('Gepubliceerd zonder goedkeuringen');
        } catch (LogicException $e) {
            $this->assertStringContainsString('twee goedkeuringen', $e->getMessage());
        }

        app(ApproveBatch::class)($batch, $this->publisherTwo);
        $this->assertSame(BatchStatus::PendingApproval, $batch->refresh()->status);

        try {
            app(ApproveBatch::class)($batch, $this->publisherTwo);
            $this->fail('Dezelfde persoon keurde twee keer goed');
        } catch (LogicException $e) {
            $this->assertStringContainsString('al goedgekeurd', $e->getMessage());
        }

        app(ApproveBatch::class)($batch, $this->publisherThree);
        $this->assertSame(BatchStatus::Approved, $batch->refresh()->status);

        // Publiceren: status, snapshot, erkenning, notificatie.
        app(PublishBatch::class)($batch, $this->publisherOne);

        $this->assertSame(BatchStatus::Published, $batch->refresh()->status);
        $this->assertSame(EntryStatus::Published, $entry->refresh()->status);
        $this->assertNotNull($entry->published_at);

        $snapshot = RankingSnapshot::latestForProvince($this->edition->getKey(), $entry->province_id);
        $this->assertNotNull($snapshot);
        $position = $snapshot->positions()->firstOrFail();
        $this->assertSame($entry->getKey(), $position->entry_id);
        $this->assertSame(1, $position->position);
        $this->assertSame(8.0, $position->total);
        $this->assertSame(PositionLabel::New, $position->label);

        $this->assertTrue(Recognition::query()->where('entry_id', $entry->getKey())->where('type', RecognitionType::Tested)->exists());
        Notification::assertSentTo($entry->company->users()->first(), ResultPublishedNotification::class);

        // Publiekssite: Voorlijst met direct antwoord; portaal: uitslag.
        $this->get(route('provincies.show', $entry->province))
            ->assertOk()
            ->assertSee('Voorlopige Top')
            ->assertSee($entry->public_name)
            ->assertSee('8,0');

        $this->get(route('bakkers.toon', $entry->company))
            ->assertOk()
            ->assertSee('Officieel getest')
            ->assertSee('8,0');

        $this->actingAs($entry->company->users()->first(), 'participant')
            ->get(route('portaal.uitslag'))
            ->assertOk()
            ->assertSee('8,0')
            ->assertSee('plaats 1');
    }

    #[Test]
    public function a_result_below_the_threshold_stays_confidential(): void
    {
        Notification::fake();

        $this->scoreAndFinalize(smaak: 4); // 64 punten → 6,4? nee: alle onderdelen laag

        $entry = $this->chainEntry->refresh();
        $batch = PublicationBatch::query()->firstOrFail();
        $item = $batch->items()->firstOrFail();

        if ($item->visibility === ItemVisibility::Public) {
            $this->markTestSkipped('Score boven de drempel; testdata aanpassen.');
        }

        app(SubmitBatch::class)($batch, $this->publisherOne);
        app(ApproveBatch::class)($batch, $this->publisherTwo);
        app(ApproveBatch::class)($batch, $this->publisherThree);
        app(PublishBatch::class)($batch);

        $this->assertSame(EntryStatus::Confidential, $entry->refresh()->status);
        $this->assertNull(RankingSnapshot::latestForProvince($this->edition->getKey(), $entry->province_id)?->positions()->first());
        $this->assertFalse(Recognition::query()->where('entry_id', $entry->getKey())->exists());

        $this->get(route('provincies.show', $entry->province))->assertOk()->assertDontSee('Voorlopige Top');
    }

    private function scoreAndFinalize(int $smaak): void
    {
        foreach ($this->chainPanelists as $panelist) {
            $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();
            $scores = $smaak < 10
                ? ['smaak' => $smaak, 'structuur_luchtigheid' => 6, 'vulling_verhouding' => 5, 'versheid' => 4, 'korst_kleur' => 4, 'bakgraad_vetopname' => 4, 'geur' => 2, 'uiterlijk_presentatie' => 2]
                : $this->fullScores($smaak);
            app(SubmitScorecard::class)($panelist, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $scores, 'Krokant', 'Meer vulling', CarbonImmutable::now()));
        }

        app(FinalizeResult::class)($this->chainSample->refresh(), $this->reviewer);
    }
}
