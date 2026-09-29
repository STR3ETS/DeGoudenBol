<?php

namespace Tests\Feature\Ranking;

use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\ApproveBatch;
use App\Domain\Ranking\Actions\ApproveCorrectionCase;
use App\Domain\Ranking\Actions\OpenCorrectionCase;
use App\Domain\Ranking\Actions\PublishBatch;
use App\Domain\Ranking\Actions\SubmitBatch;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Enums\CorrectionCaseStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Ranking\Notifications\CorrectionAppliedNotification;
use App\Domain\Testing\Actions\ApproveScoreCorrection;
use App\Domain\Testing\Actions\FinalizeResult;
use App\Domain\Testing\Actions\RequestScoreCorrection;
use App\Domain\Testing\Actions\SubmitScorecard;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Models\Scorecard;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsTestChain;
use Tests\TestCase;

class CorrectionCaseTest extends TestCase
{
    use BuildsTestChain, RefreshDatabase;

    private User $reviewer;

    private User $secondReviewer;

    /** @var list<User> */
    private array $publishers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestChain();

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole(StaffRole::Reviewer->value);
        $this->secondReviewer = User::factory()->create();
        $this->secondReviewer->assignRole(StaffRole::Reviewer->value);

        for ($i = 0; $i < 3; $i++) {
            $publisher = User::factory()->create();
            $publisher->assignRole(StaffRole::Publisher->value);
            $this->publishers[] = $publisher;
        }
    }

    #[Test]
    public function a_published_result_only_changes_through_a_correction_case_with_two_approvals(): void
    {
        Notification::fake();

        foreach ($this->chainPanelists as $panelist) {
            $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();
            app(SubmitScorecard::class)($panelist, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $this->fullScores(), null, null, CarbonImmutable::now()));
        }

        app(FinalizeResult::class)($this->chainSample->refresh(), $this->reviewer);
        $batch = PublicationBatch::query()->where('status', BatchStatus::Draft)->firstOrFail();
        app(SubmitBatch::class)($batch, $this->publishers[0]);
        app(ApproveBatch::class)($batch, $this->publishers[1]);
        app(ApproveBatch::class)($batch, $this->publishers[2]);
        app(PublishBatch::class)($batch->refresh());

        $entry = $this->chainEntry->refresh();
        $this->assertSame(EntryStatus::Published, $entry->status);
        $this->assertSame(8.0, RankingSnapshot::latestForProvince($this->edition->getKey(), $entry->province_id)->positions()->first()->total);

        // Zonder dossier kan scorecontrole niets meer veranderen aan een definitieve uitslag.
        $card = Scorecard::query()->firstOrFail();

        try {
            app(RequestScoreCorrection::class)($card, ['smaak' => 25], 'Verkeerd overgenomen', $this->reviewer);
            $this->fail('Correctie op definitieve uitslag toegestaan zonder dossier');
        } catch (LogicException $e) {
            $this->assertStringContainsString('correctiedossier', $e->getMessage());
        }

        // Dossier openen zet de uitslag terug naar scorecontrole; daarna een gewone kaartcorrectie met vier ogen.
        $case = app(OpenCorrectionCase::class)($entry, 'Invoerfout ontdekt na publicatie', $this->reviewer);
        $this->assertSame(ResultStatus::Pending, $this->chainSample->refresh()->result->status);

        $card = $card->fresh(); // de relaties van de eerdere poging zijn verouderd
        $correction = app(RequestScoreCorrection::class)($card, ['smaak' => 25], 'Verkeerd overgenomen', $this->reviewer);
        app(ApproveScoreCorrection::class)($correction, $this->secondReviewer);

        try {
            app(ApproveCorrectionCase::class)($case, $this->reviewer);
            $this->fail('Scorecontrole keurde een dossier goed');
        } catch (LogicException $e) {
            $this->assertStringContainsString('Alleen de rol Publicatie', $e->getMessage());
        }

        app(ApproveCorrectionCase::class)($case, $this->publishers[1]);
        $this->assertSame(CorrectionCaseStatus::PendingApproval, $case->refresh()->status);

        app(ApproveCorrectionCase::class)($case, $this->publishers[2]);

        $case->refresh();
        $this->assertSame(CorrectionCaseStatus::Applied, $case->status);
        $this->assertSame(8.0, (float) $case->original_snapshot['total']);
        $this->assertSame(8.1, (float) $case->new_snapshot['total']);
        $this->assertSame(ResultStatus::Final, $this->chainSample->refresh()->result->status);
        Notification::assertSentTo($entry->company->users()->first(), CorrectionAppliedNotification::class);

        // De nieuwe uitslag staat in de eerstvolgende batch; na publicatie volgt een nieuwe snapshot.
        $next = PublicationBatch::query()->where('status', BatchStatus::Draft)->firstOrFail();
        $this->assertSame(8.1, $next->items()->firstOrFail()->total());

        $next->forceFill(['status' => BatchStatus::Approved])->save();
        app(PublishBatch::class)($next);

        $this->assertSame(8.1, RankingSnapshot::latestForProvince($this->edition->getKey(), $entry->province_id)->positions()->first()->total);
    }
}
