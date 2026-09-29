<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Testing\Actions\SubmitScorecard;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Domain\Testing\Exceptions\ScorecardRejectedException;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\ServingAssignment;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Eén monster per scherm: testnummer groot, acht onderdelen, lopend totaal. Na indienen vergrendeld.
 */
class ScorecardController extends Controller
{
    public function show(Request $request, ServingAssignment $assignment): View
    {
        $panelist = $this->authorizeAssignment($request, $assignment);
        $assignment->load(['sample.intake', 'session']);
        $scorecard = $assignment->findScorecard();

        if ($scorecard !== null) {
            return view('panel.ingediend', ['assignment' => $assignment, 'panelist' => $panelist, 'scorecard' => $scorecard]);
        }

        $model = ScoringModel::query()->where('edition_id', $assignment->session->edition_id)->where('is_active', true)->with('criteria')->firstOrFail();

        return view('panel.scorekaart', [
            'assignment' => $assignment,
            'panelist' => $panelist,
            'criteria' => $model->criteria,
            'uuid' => (string) Str::uuid(),
            'freshUntil' => $assignment->sample->intake?->freshness_expires_at,
            'sessionOpen' => $assignment->session->status->acceptsScorecards(),
        ]);
    }

    /**
     * Fallback zonder JavaScript: gewone formulierpost, idempotent op de vooraf uitgedeelde uuid.
     */
    public function store(Request $request, ServingAssignment $assignment, SubmitScorecard $submit): RedirectResponse
    {
        $panelist = $this->authorizeAssignment($request, $assignment);

        $data = $request->validate([
            'uuid' => ['required', 'uuid'],
            'scores' => ['required', 'array'],
            'scores.*' => ['required', 'integer', 'min:0'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'opportunities' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $outcome = $submit($panelist, new ScorecardSubmission(
                uuid: $data['uuid'],
                assignmentId: $assignment->getKey(),
                scores: $data['scores'],
                strengths: $data['strengths'] ?? null,
                opportunities: $data['opportunities'] ?? null,
                submittedAt: CarbonImmutable::now(),
            ));
        } catch (ScorecardRejectedException $exception) {
            throw ValidationException::withMessages(['scores' => $exception->getMessage()]);
        }

        return redirect()->route('panel.overzicht', ['ingediend' => $outcome->scorecard->sample->sample_number]);
    }

    private function authorizeAssignment(Request $request, ServingAssignment $assignment): Panelist
    {
        /** @var Panelist $panelist */
        $panelist = $request->attributes->get('panelist');

        abort_unless($assignment->panelist_id === $panelist->getKey(), 403, 'Dit monster staat niet op uw schema.');

        return $panelist;
    }
}
