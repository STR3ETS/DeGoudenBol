<?php

namespace App\Domain\Testing\Actions;

use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Domain\Testing\Data\SubmissionOutcome;
use App\Domain\Testing\Enums\AssignmentStatus;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Enums\ScorecardSource;
use App\Domain\Testing\Exceptions\ScorecardRejectedException;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Testing\Models\ServingAssignment;
use Illuminate\Support\Facades\DB;

/**
 * Stap 6 uit het kernproces: kaart indienen. Idempotent op de client-UUID zodat de offline-
 * wachtrij nooit dubbele of verloren kaarten oplevert. Na indienen is de kaart vergrendeld.
 */
final class SubmitScorecard
{
    public function __construct(private readonly RecalculateResult $recalculate) {}

    public function __invoke(Panelist $panelist, ScorecardSubmission $submission): SubmissionOutcome
    {
        $existing = Scorecard::query()->where('uuid', $submission->uuid)->first();

        if ($existing !== null) {
            return new SubmissionOutcome($existing, true);
        }

        $assignment = ServingAssignment::query()->with(['session', 'sample.intake'])->find($submission->assignmentId)
            ?? throw new ScorecardRejectedException('Onbekende uitservering.');

        if ($assignment->panelist_id !== $panelist->getKey()) {
            throw new ScorecardRejectedException('Dit monster staat niet op uw schema.');
        }

        if (! $assignment->session->status->acceptsScorecards()) {
            throw new ScorecardRejectedException('Deze sessie is gesloten; de kaart kan niet meer worden ingediend.');
        }

        $expiresAt = $assignment->sample->intake?->freshness_expires_at;

        if ($expiresAt === null || $submission->submittedAt->greaterThan($expiresAt)) {
            throw new ScorecardRejectedException('De versheid van dit monster is verlopen; scoren is geblokkeerd.');
        }

        $duplicate = Scorecard::query()->where('sample_id', $assignment->sample_id)->where('panelist_id', $panelist->getKey())->first();

        if ($duplicate !== null) {
            return new SubmissionOutcome($duplicate, true);
        }

        $scores = $this->validatedScores($assignment->session->edition_id, $submission->scores);

        $card = DB::connection('testing')->transaction(function () use ($assignment, $panelist, $submission, $scores): Scorecard {
            $card = Scorecard::query()->create([
                'uuid' => $submission->uuid,
                'sample_id' => $assignment->sample_id,
                'panelist_id' => $panelist->getKey(),
                'test_session_id' => $assignment->test_session_id,
                'scores' => $scores,
                'strengths' => $submission->strengths,
                'opportunities' => $submission->opportunities,
                'submitted_at' => $submission->submittedAt,
                'source' => ScorecardSource::App,
            ]);

            $assignment->forceFill(['status' => AssignmentStatus::Scored])->save();

            if (in_array($assignment->sample->status, [SampleStatus::Numbered, SampleStatus::Scheduled], true)) {
                $assignment->sample->forceFill(['status' => SampleStatus::Scored])->save();
            }

            return $card;
        });

        ($this->recalculate)($assignment->sample->refresh());

        return new SubmissionOutcome($card, false);
    }

    /**
     * Hele punten binnen 0 … max per onderdeel; ieder onderdeel van het actieve model is verplicht.
     *
     * @param  array<string, mixed>  $scores
     * @return array<string, int>
     */
    private function validatedScores(int $editionId, array $scores): array
    {
        $model = ScoringModel::query()->where('edition_id', $editionId)->where('is_active', true)->with('criteria')->first()
            ?? throw new ScorecardRejectedException('Geen actief beoordelingsmodel.');

        $validated = [];

        foreach ($model->criteria as $criterion) {
            $value = $scores[$criterion->code] ?? null;

            if ($value === null || $value === '' || ! is_numeric($value) || (int) $value != $value) {
                throw new ScorecardRejectedException("Onderdeel {$criterion->name} mist of is geen heel getal.");
            }

            $value = (int) $value;

            if ($value < 0 || $value > $criterion->max_points) {
                throw new ScorecardRejectedException("Onderdeel {$criterion->name} moet tussen 0 en {$criterion->max_points} liggen.");
            }

            $validated[$criterion->code] = $value;
        }

        return $validated;
    }
}
