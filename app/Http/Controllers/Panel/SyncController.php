<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Testing\Actions\SubmitScorecard;
use App\Domain\Testing\Data\ScorecardSubmission;
use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Exceptions\ScorecardRejectedException;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Testing\Models\TestSession;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * JSON-eindpunten van de panel-app: schema ophalen en de offline-wachtrij synchroniseren.
 * De API geeft nooit de inhoud van een ingediende kaart terug.
 */
class SyncController extends Controller
{
    public function schedule(Request $request): JsonResponse
    {
        /** @var Panelist $panelist */
        $panelist = $request->attributes->get('panelist');

        $sessions = TestSession::query()
            ->whereHas('panelists', fn ($query) => $query->whereKey($panelist->getKey()))
            ->whereIn('status', [SessionStatus::Planned, SessionStatus::Running])
            ->where('ends_at', '>=', now()->subHours(6))
            ->with(['assignments' => fn ($query) => $query->where('panelist_id', $panelist->getKey())->orderBy('serving_order')->with('sample.intake')])
            ->orderBy('starts_at')
            ->get();

        $submitted = Scorecard::query()
            ->where('panelist_id', $panelist->getKey())
            ->whereIn('test_session_id', $sessions->modelKeys())
            ->pluck('sample_id')
            ->all();

        $editionId = $sessions->first()?->edition_id;
        $criteria = $editionId
            ? ScoringModel::query()->where('edition_id', $editionId)->where('is_active', true)->with('criteria')->first()?->criteria
            : null;

        return response()->json([
            'panelist' => $panelist->display_code,
            'sessions' => $sessions->map(fn (TestSession $session) => [
                'id' => $session->getKey(),
                'name' => $session->displayName(),
                'starts_at' => $session->starts_at->toIso8601String(),
                'status' => $session->status->value,
                'assignments' => $session->assignments->map(fn ($assignment) => [
                    'id' => $assignment->getKey(),
                    'order' => $assignment->serving_order,
                    'sample' => $assignment->sample->label(),
                    'fresh_until' => $assignment->sample->intake?->freshness_expires_at?->toIso8601String(),
                    'submitted' => in_array($assignment->sample_id, $submitted, true),
                    'url' => route('panel.monster', $assignment),
                ])->values(),
            ])->values(),
            'criteria' => $criteria?->map(fn ($criterion) => [
                'code' => $criterion->code,
                'name' => $criterion->name,
                'description' => $criterion->description,
                'max' => $criterion->max_points,
            ])->values() ?? [],
        ]);
    }

    public function store(Request $request, SubmitScorecard $submit): JsonResponse
    {
        /** @var Panelist $panelist */
        $panelist = $request->attributes->get('panelist');

        $data = $request->validate([
            'cards' => ['required', 'array', 'max:50'],
            'cards.*.uuid' => ['required', 'uuid'],
            'cards.*.assignment_id' => ['required', 'integer'],
            'cards.*.scores' => ['required', 'array'],
            'cards.*.strengths' => ['nullable', 'string', 'max:2000'],
            'cards.*.opportunities' => ['nullable', 'string', 'max:2000'],
            'cards.*.submitted_at' => ['required', 'date'],
        ]);

        $results = [];

        foreach ($data['cards'] as $card) {
            try {
                $outcome = $submit($panelist, new ScorecardSubmission(
                    uuid: $card['uuid'],
                    assignmentId: (int) $card['assignment_id'],
                    scores: $card['scores'],
                    strengths: $card['strengths'] ?? null,
                    opportunities: $card['opportunities'] ?? null,
                    submittedAt: CarbonImmutable::parse($card['submitted_at'])->utc(),
                ));

                $results[$card['uuid']] = ['status' => $outcome->wasDuplicate ? 'duplicate' : 'accepted', 'sample' => $outcome->scorecard->sample->label()];
            } catch (ScorecardRejectedException $exception) {
                $results[$card['uuid']] = ['status' => 'rejected', 'message' => $exception->getMessage()];
            } catch (Throwable $exception) {
                report($exception);
                $results[$card['uuid']] = ['status' => 'error', 'message' => 'Onverwachte fout; de kaart blijft in de wachtrij.'];
            }
        }

        return response()->json(['results' => $results]);
    }
}
