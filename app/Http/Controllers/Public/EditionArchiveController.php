<?php

namespace App\Http\Controllers\Public;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Archief: uitslagen van eerdere edities blijven vindbaar (/editie/{jaar}).
 */
class EditionArchiveController extends Controller
{
    public function index(): View
    {
        $editions = Edition::query()
            ->whereIn('status', [EditionStatus::Published, EditionStatus::Archived])
            ->orderByDesc('year')
            ->get();

        return view('public.edities.index', ['editions' => $editions]);
    }

    public function show(Edition $edition): View
    {
        abort_unless(in_array($edition->status, [EditionStatus::Frozen, EditionStatus::Published, EditionStatus::Archived], true), 404);

        $edition->load('provinces');
        $lists = [];

        foreach ($edition->provinces as $province) {
            $snapshot = RankingSnapshot::latestForProvince($edition->getKey(), $province->getKey());

            if ($snapshot === null || ! $snapshot->isFrozen()) {
                continue;
            }

            $lists[] = [
                'province' => $province,
                'positions' => $snapshot->positions()->with('entry.company')->get()->take($edition->settings->provincialListLength),
            ];
        }

        $national = RankingSnapshot::latestNational($edition->getKey());

        return view('public.edities.show', [
            'edition' => $edition,
            'lists' => $lists,
            'national' => $national?->positions()->with(['entry.company', 'entry.province'])->get()->take($edition->settings->nationalListLength) ?? collect(),
        ]);
    }
}
