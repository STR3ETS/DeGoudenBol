<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Testing\Enums\SessionStatus;
use App\Domain\Testing\Models\Panelist;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Testing\Models\TestSession;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Uitserveerschema van dit panellid: sessies van vandaag en de eerstvolgende dagen.
 */
class OverviewController extends Controller
{
    public function __invoke(Request $request): View
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
            ->get()
            ->keyBy('sample_id');

        return view('panel.overzicht', [
            'panelist' => $panelist,
            'sessions' => $sessions,
            'submittedBySample' => $submitted,
            'submittedNumber' => $request->query('ingediend'),
            'queued' => $request->boolean('wachtrij'),
        ]);
    }
}
