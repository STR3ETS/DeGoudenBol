<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Services\SponsorPlacements;
use App\Domain\Edition\Models\Edition;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Landelijke finale: finalisten (na de publicatiedag) en de landelijke lijst (na de landelijke uitslag).
 */
class FinaleController extends Controller
{
    public function __invoke(SponsorPlacements $placements): View
    {
        $edition = Edition::query()->current()->first();
        $revealed = $edition !== null && $edition->provinces()->whereNotNull('edition_province.revealed_at')->exists();

        $finalists = $edition && $revealed
            ? Finalist::query()
                ->where('edition_id', $edition->getKey())
                ->participating()
                ->with(['entry.company.primaryLocation', 'entry.company.profile', 'province'])
                ->get()
                ->sortBy(fn (Finalist $finalist) => $finalist->province->sort)
                ->values()
            : collect();

        $national = $edition ? RankingSnapshot::latestNational($edition->getKey()) : null;
        $positions = $national?->positions()->with(['entry.company.primaryLocation', 'entry.province'])->get() ?? collect();

        return view('public.finale', [
            'edition' => $edition,
            'revealed' => $revealed,
            'finalists' => $finalists,
            'national' => $national,
            'positions' => $positions,
            'listLength' => $edition?->settings->nationalListLength ?? 5,
            'sponsors' => $edition ? $placements->finale($edition) : collect(),
        ]);
    }
}
