<?php

namespace Tests\Feature\Ranking;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Ranking\Actions\PublishPersonalReport;
use App\Domain\Ranking\Enums\ReportStatus;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Notifications\ReportAvailableNotification;
use App\Domain\Testing\Actions\FinalizeResult;
use App\Domain\Testing\Actions\SubmitScorecard;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsTestChain;
use Tests\TestCase;

/**
 * Persoonlijk rapport en geheime ranking (briefing 29 okt §4 en §7).
 */
class PersonalReportTest extends TestCase
{
    use BuildsTestChain, RefreshDatabase;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestChain();

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole(StaffRole::Reviewer->value);
    }

    #[Test]
    public function the_report_is_due_within_three_days_and_the_ranking_waits_for_the_main_publication(): void
    {
        $this->scoreAndFinalize();

        $report = ConfidentialReport::query()->where('entry_id', $this->chainEntry->getKey())->firstOrFail();
        $this->assertSame(ReportStatus::Draft, $report->status);
        $this->assertNotNull($report->tested_on);
        $this->assertTrue($report->due_at->isBetween(now()->addDays(2), now()->addDays(4)));

        $batch = PublicationBatch::query()->where('edition_id', $this->edition->getKey())->firstOrFail();
        $this->assertTrue($batch->scheduled_at->equalTo($this->edition->main_publication_at));
    }

    #[Test]
    public function releasing_the_report_mails_the_owner_once_and_shows_the_scores_in_the_portal(): void
    {
        Notification::fake();

        $this->scoreAndFinalize();
        $owner = $this->chainEntry->company->users()->wherePivot('role', CompanyUserRole::Owner->value)->firstOrFail();
        $report = ConfidentialReport::query()->where('entry_id', $this->chainEntry->getKey())->firstOrFail();

        $this->actingAs($owner, 'participant')->get(route('portaal.rapport', $this->chainEntry->company))->assertNotFound();

        app(PublishPersonalReport::class)($report, $this->reviewer);
        app(PublishPersonalReport::class)($report->fresh(), $this->reviewer);

        Notification::assertSentToTimes($owner, ReportAvailableNotification::class, 1);

        $this->actingAs($owner, 'participant')
            ->get(route('portaal.rapport', $this->chainEntry->company))
            ->assertOk()
            ->assertSee('Officieel beoordelingsrapport')
            ->assertSee('Bakkerij Testers')
            ->assertSee('Vetopname en mondgevoel')
            ->assertSee('Totaalindruk')
            ->assertSee('80')
            ->assertSee('Download als pdf');

        $this->actingAs($owner, 'participant')
            ->get(route('portaal.uitslag', $this->chainEntry->company))
            ->assertOk()
            ->assertSee('Persoonlijk rapport')
            ->assertSee('Landelijke uitslag')
            ->assertSee('De ranglijst blijft geheim');
    }

    #[Test]
    public function the_report_mail_follows_the_supplied_template(): void
    {
        $this->scoreAndFinalize();
        $owner = $this->chainEntry->company->users()->firstOrFail();

        $html = (string) (new ReportAvailableNotification($this->chainEntry->fresh()))->toMail($owner)->render();

        $this->assertStringContainsString('Uw keuringsrapport', $html);
        $this->assertStringContainsString('staat voor u klaar.', $html);
        $this->assertStringContainsString('Officieel beoordelingsrapport', $html);
        $this->assertStringContainsString('BEKIJK UW KEURINGSRAPPORT', mb_strtoupper($html));
        $this->assertStringContainsString('En de landelijke uitslag?', $html);
        $this->assertStringContainsString('#1e1207', $html);
    }

    private function scoreAndFinalize(): void
    {
        foreach ($this->chainPanelists as $panelist) {
            $assignment = $this->chainSession->assignments()->where('panelist_id', $panelist->getKey())->firstOrFail();
            app(SubmitScorecard::class)($panelist, new ScorecardSubmission((string) Str::uuid(), $assignment->getKey(), $this->fullScores(), 'Luchtig en krokant', 'Iets meer krenten', CarbonImmutable::now()));
        }

        app(FinalizeResult::class)($this->chainSample->refresh(), $this->reviewer);
        $this->chainEntry->refresh();
    }
}
