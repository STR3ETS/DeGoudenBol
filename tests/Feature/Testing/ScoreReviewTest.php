<?php

namespace Tests\Feature\Testing;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Actions\ApproveScoreCorrection;
use App\Domain\Testing\Actions\EnterPaperScorecard;
use App\Domain\Testing\Actions\FinalizeResult;
use App\Domain\Testing\Actions\InvalidateScorecard;
use App\Domain\Testing\Actions\RequestScoreCorrection;
use App\Domain\Testing\Actions\SubmitScorecard;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Enums\ScorecardSource;
use App\Domain\Testing\Events\ResultFinalized;
use App\Domain\Testing\Exceptions\ScorecardRejectedException;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\Scorecard;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsTestChain;
use Tests\TestCase;

class ScoreReviewTest extends TestCase
{
    use BuildsTestChain, RefreshDatabase;

    private User $reviewer;

    private User $secondReviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestChain();

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole(StaffRole::Reviewer->value);
        $this->secondReviewer = User::factory()->create();
        $this->secondReviewer->assignRole(StaffRole::Reviewer->value);
    }

    #[Test]
    public function submitting_cards_is_idempotent_locked_and_recalculates_the_result(): void
    {
        $panelist = $this->chainPanelists[0];
        $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();
        $uuid = (string) Str::uuid();

        $submission = new ScorecardSubmission($uuid, $assignment->getKey(), $this->fullScores(), 'Mooi krokant', 'Iets meer vulling', CarbonImmutable::now());
        $first = app(SubmitScorecard::class)($panelist, $submission);
        $second = app(SubmitScorecard::class)($panelist, $submission);

        $this->assertFalse($first->wasDuplicate);
        $this->assertTrue($second->wasDuplicate);
        $this->assertSame($first->scorecard->getKey(), $second->scorecard->getKey());
        $this->assertSame(1, Scorecard::query()->count());
        $this->assertTrue($first->scorecard->hashMatches());
        $this->assertSame(SampleStatus::Scored, $this->chainSample->refresh()->status);

        $result = $this->chainSample->result;
        $this->assertSame(1, $result->card_count);
        $this->assertSame(8.0, $result->total);
        $this->assertSame(ResultStatus::Pending, $result->status);
        $this->assertTrue($result->flags['missing_cards']);

        $this->expectException(LogicException::class);
        $first->scorecard->forceFill(['scores' => ['smaak' => 1]])->save();
    }

    #[Test]
    public function cards_are_rejected_for_the_wrong_panelist_closed_sessions_out_of_range_scores_and_stale_samples(): void
    {
        $panelist = $this->chainPanelists[0];
        $other = $this->chainPanelists[1];
        $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();

        try {
            app(SubmitScorecard::class)($other, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $this->fullScores(), null, null, CarbonImmutable::now()));
            $this->fail('Verkeerde panelist geaccepteerd');
        } catch (ScorecardRejectedException $e) {
            $this->assertStringContainsString('niet op uw schema', $e->getMessage());
        }

        try {
            app(SubmitScorecard::class)($panelist, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $this->fullScores(smaak: 26), null, null, CarbonImmutable::now()));
            $this->fail('Score boven maximum geaccepteerd');
        } catch (ScorecardRejectedException $e) {
            $this->assertStringContainsString('tussen 0 en 25', $e->getMessage());
        }

        try {
            app(SubmitScorecard::class)($panelist, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $this->fullScores(), null, null, CarbonImmutable::now()->addHours(4)));
            $this->fail('Verlopen versheid geaccepteerd');
        } catch (ScorecardRejectedException $e) {
            $this->assertStringContainsString('versheid', $e->getMessage());
        }

        $this->chainSession->forceFill(['status' => 'closed'])->save();

        $this->expectException(ScorecardRejectedException::class);
        app(SubmitScorecard::class)($panelist, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $this->fullScores(), null, null, CarbonImmutable::now()));
    }

    #[Test]
    public function review_can_invalidate_a_card_correct_with_four_eyes_and_finalize_with_enough_cards(): void
    {
        Event::fake([ResultFinalized::class]);

        foreach ($this->chainPanelists as $index => $panelist) {
            $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();
            // Het zesde panellid wijkt 16 punten af van de mediaan (80): meer dan de toegestane 15.
            app(SubmitScorecard::class)($panelist, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $this->fullScores(smaak: $index === 5 ? 4 : 20), null, null, CarbonImmutable::now()));
        }

        $this->chainSample->refresh();
        $this->assertSame(6, $this->chainSample->result->card_count);
        $this->assertSame(1, count($this->chainSample->result->flags['outliers']));

        // Ongeldig verklaren: nog maar 5 kaarten → definitief maken faalt.
        $outlier = Scorecard::query()->where('uuid', $this->chainSample->result->flags['outliers'][0])->firstOrFail();
        app(InvalidateScorecard::class)($outlier, 'Dubbele invoer', $this->reviewer);
        $this->assertFalse($outlier->refresh()->is_valid);
        $this->assertSame(5, $this->chainSample->refresh()->result->card_count);

        try {
            app(FinalizeResult::class)($this->chainSample, $this->reviewer);
            $this->fail('Definitief gemaakt met te weinig kaarten');
        } catch (LogicException $e) {
            $this->assertStringContainsString('Te weinig geldige kaarten', $e->getMessage());
        }

        // Papieren kaart als noodroute voor een zevende panellid.
        $extraUser = User::factory()->create();
        $extraUser->assignRole(StaffRole::Panelist->value);
        $extra = Panelist::factory()->create(['user_id' => $extraUser->getKey()]);
        $this->chainSession->panelists()->attach($extra->getKey());
        $assignment = $this->chainSession->assignments()->create(['panelist_id' => $extra->getKey(), 'sample_id' => $this->chainSample->getKey(), 'serving_order' => 1, 'status' => 'pending']);

        $firstEntry = app(EnterPaperScorecard::class)($assignment, $this->fullScores(smaak: 22), null, null, $this->reviewer);
        $this->assertFalse($firstEntry->isConfirmed());

        $mismatch = app(EnterPaperScorecard::class)($assignment, $this->fullScores(smaak: 21), null, null, $this->secondReviewer);
        $this->assertTrue($mismatch->mismatch);
        $this->assertFalse($mismatch->isConfirmed());

        $third = User::factory()->create();
        $third->assignRole(StaffRole::Reviewer->value);
        $confirmed = app(EnterPaperScorecard::class)($assignment, $this->fullScores(smaak: 22), null, null, $third);
        $this->assertTrue($confirmed->isConfirmed());
        $this->assertSame(ScorecardSource::Paper, $confirmed->scorecard->source);
        $this->assertSame(6, $this->chainSample->refresh()->result->card_count);

        // Correctie met vier ogen.
        $card = Scorecard::query()->where('panelist_id', $this->chainPanelists[0]->getKey())->firstOrFail();
        $correction = app(RequestScoreCorrection::class)($card, ['smaak' => 24], 'Verkeerd overgenomen', $this->reviewer);

        try {
            app(ApproveScoreCorrection::class)($correction, $this->reviewer);
            $this->fail('Aanvrager keurde eigen correctie goed');
        } catch (LogicException $e) {
            $this->assertStringContainsString('eigen correctie', $e->getMessage());
        }

        try {
            app(FinalizeResult::class)($this->chainSample->refresh(), $this->reviewer);
            $this->fail('Definitief gemaakt met open correctie');
        } catch (LogicException $e) {
            $this->assertStringContainsString('correcties open', $e->getMessage());
        }

        app(ApproveScoreCorrection::class)($correction, $this->secondReviewer);
        $this->assertSame(20, $card->refresh()->scores['smaak'], 'De oorspronkelijke kaart blijft onveranderd.');
        $this->assertTrue($card->hashMatches());

        $result = app(FinalizeResult::class)($this->chainSample->refresh(), $this->reviewer);

        $this->assertSame(ResultStatus::Final, $result->status);
        $this->assertSame($this->reviewer->getKey(), $result->finalized_by);
        $this->assertSame(SampleStatus::Final, $this->chainSample->refresh()->status);
        $this->assertEqualsWithDelta(8.1, $result->total, 0.001);
        Event::assertDispatched(ResultFinalized::class);
    }
}
