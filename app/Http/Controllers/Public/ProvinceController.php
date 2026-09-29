<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Services\SponsorPlacements;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Services\ProvinceCapacity;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Http\Controllers\Controller;
use App\Support\PublicCache;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class ProvinceController extends Controller
{
    public function index(ProvinceCapacity $capacity): View
    {
        $edition = Edition::query()->current()->with('provinces')->first();

        return view('public.provincies.index', [
            'edition' => $edition,
            'provinces' => $edition?->provinces ?? Province::query()->orderBy('sort')->get(),
            'availability' => $edition ? $capacity->overview($edition) : [],
        ]);
    }

    public function show(Province $province, ProvinceCapacity $capacity, SponsorPlacements $placements): View
    {
        $edition = Edition::query()->current()->first();

        $snapshotId = $edition
            ? Cache::remember(PublicCache::provinceKey($edition->getKey(), $province->getKey()), now()->addMinutes(PublicCache::TTL_MINUTES), fn () => RankingSnapshot::latestForProvince($edition->getKey(), $province->getKey())?->getKey() ?? 0)
            : 0;

        $snapshot = $snapshotId ? RankingSnapshot::query()->with(['positions.entry.company.profile', 'positions.entry.company.primaryLocation'])->find($snapshotId) : null;
        $positions = $snapshot?->positions->filter(fn ($position) => $position->entry?->status->isPubliclyVisible())->values() ?? collect();
        $listLength = $edition?->settings->provincialListLength ?? 10;

        $entries = $edition
            ? Entry::query()
                ->where('edition_id', $edition->getKey())
                ->where('province_id', $province->getKey())
                ->confirmed()
                ->whereNotIn('id', $positions->pluck('entry_id'))
                ->with(['company.profile', 'company.primaryLocation', 'province', 'edition', 'publicationItems.batch'])
                ->orderBy('public_name')
                ->get()
            : collect();

        $pivot = $edition?->provinces()->whereKey($province->getKey())->first()?->pivot;
        $pendingReveal = $edition?->status === EditionStatus::Frozen && $pivot !== null && $pivot->revealed_at === null
            ? ($pivot->reveal_at ?? $edition->main_publication_at)
            : null;

        return view('public.provincies.show', [
            'edition' => $edition,
            'province' => $province,
            'snapshot' => $snapshot,
            'pendingReveal' => $pendingReveal,
            'top' => $positions->filter(fn ($position) => $position->position <= $listLength)->values(),
            'rest' => $positions->filter(fn ($position) => $position->position > $listLength)->values(),
            'listLength' => $listLength,
            'entries' => $entries,
            'availability' => $edition ? ($capacity->overview($edition)[$province->slug] ?? null) : null,
            'partner' => $edition ? $placements->provincePartner($edition, $province) : null,
            'pressRelease' => $edition ? PressRelease::query()->published()->where('edition_id', $edition->getKey())->where('province_id', $province->getKey())->orderByDesc('published_at')->first() : null,
        ]);
    }
}
