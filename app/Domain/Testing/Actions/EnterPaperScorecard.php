<?php

namespace App\Domain\Testing\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Testing\Data\PaperEntryOutcome;
use App\Domain\Testing\Enums\AssignmentStatus;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Enums\ScorecardSource;
use App\Domain\Testing\Models\PaperEntry;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Testing\Models\ServingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Noodroute: papieren kaart. Twee mensen voeren onafhankelijk in; pas bij twee gelijke invoeren
 * ontstaat een scorekaart met bron "paper".
 */
final class EnterPaperScorecard
{
    public function __construct(
        private readonly RecalculateResult $recalculate,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, int>  $scores
     */
    public function __invoke(ServingAssignment $assignment, array $scores, ?string $strengths, ?string $opportunities, User $enteredBy): PaperEntryOutcome
    {
        $assignment->loadMissing(['sample', 'session']);

        if (Scorecard::query()->where('sample_id', $assignment->sample_id)->where('panelist_id', $assignment->panelist_id)->exists()) {
            throw new LogicException('Voor dit panellid en monster bestaat al een scorekaart.');
        }

        if (PaperEntry::query()->where('sample_id', $assignment->sample_id)->where('panelist_id', $assignment->panelist_id)->where('entered_by', $enteredBy->getKey())->exists()) {
            throw new LogicException('U heeft deze kaart al ingevoerd; de tweede invoer moet door iemand anders gebeuren.');
        }

        $entry = PaperEntry::query()->create([
            'sample_id' => $assignment->sample_id,
            'panelist_id' => $assignment->panelist_id,
            'test_session_id' => $assignment->test_session_id,
            'scores' => array_map('intval', $scores),
            'strengths' => $strengths,
            'opportunities' => $opportunities,
            'entered_by' => $enteredBy->getKey(),
        ]);

        $others = PaperEntry::query()
            ->where('sample_id', $assignment->sample_id)
            ->where('panelist_id', $assignment->panelist_id)
            ->whereKeyNot($entry->getKey())
            ->get();

        if ($others->isEmpty()) {
            return new PaperEntryOutcome($entry, null, false);
        }

        if ($others->first(fn (PaperEntry $other) => $other->matches($entry)) === null) {
            return new PaperEntryOutcome($entry, null, true);
        }

        $card = DB::connection('testing')->transaction(function () use ($assignment, $entry): Scorecard {
            $card = Scorecard::query()->create([
                'uuid' => (string) Str::uuid(),
                'sample_id' => $assignment->sample_id,
                'panelist_id' => $assignment->panelist_id,
                'test_session_id' => $assignment->test_session_id,
                'scores' => $entry->normalizedScores(),
                'strengths' => $entry->strengths,
                'opportunities' => $entry->opportunities,
                'submitted_at' => now(),
                'source' => ScorecardSource::Paper,
            ]);

            $assignment->forceFill(['status' => AssignmentStatus::Scored])->save();

            if (in_array($assignment->sample->status, [SampleStatus::Numbered, SampleStatus::Scheduled], true)) {
                $assignment->sample->forceFill(['status' => SampleStatus::Scored])->save();
            }

            return $card;
        });

        ($this->recalculate)($assignment->sample->refresh());
        $this->audit->record('scorecard.paper_confirmed', null, ['card' => $card->uuid], $enteredBy);

        return new PaperEntryOutcome($entry, $card, false);
    }
}
