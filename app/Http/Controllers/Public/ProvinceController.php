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
use App\Support\Site\SiteFacts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class ProvinceController extends Controller
{
    public function index(ProvinceCapacity $capacity): View
    {
        $edition = Edition::query()->current()->with('provinces')->first();

        return view('site.pages.provincies', [
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

        $overview = $edition ? $capacity->overview($edition) : [];
        $availability = $overview[$province->slug] ?? null;
        $top = $positions->filter(fn ($position) => $position->position <= $listLength)->values();
        $leader = $top->first()?->entry;
        $year = $edition?->year ?? now()->year;

        return view('site.provincie', [
            'edition' => $edition,
            'province' => $province,
            'snapshot' => $snapshot,
            'pendingReveal' => $pendingReveal,
            'top' => $top,
            'rest' => $positions->filter(fn ($position) => $position->position > $listLength)->values(),
            'listLength' => $listLength,
            'entries' => $entries,
            'availability' => $availability,
            'capacity' => $availability['capacity'] ?? ($edition?->settings->capacityPerProvince ?? 50),
            'taken' => $availability['taken'] ?? 0,
            'available' => $availability['available'] ?? ($edition?->settings->capacityPerProvince ?? 50),
            'capacityTotal' => (int) array_sum(array_column($overview, 'capacity')) ?: 600,
            'startLabel' => SiteFacts::startLabel($edition) ?? '1 november',
            'threshold' => number_format($edition?->settings->publishThreshold ?? 5.0, 1, ',', '.'),
            'mapInfo' => SiteFacts::mapInfo($edition),
            'metaDescription' => $leader
                ? "De Voorlijst van De Gouden Bol {$year} voor {$province->name}: op dit moment komt de beste oliebol van {$leader->public_name}. Alle gepubliceerde scores en de provinciale Top {$listLength}."
                : "De Voorlijst en provinciale Top {$listLength} van De Gouden Bol voor {$province->name}: de beste oliebollen van {$province->name}, blind getest en openbaar vanaf een score van 5,0.",
            'structuredData' => '<script type="application/ld+json">'.json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => "Voorlijst {$province->name} – De Gouden Bol {$year}",
                'itemListElement' => $top->map(fn ($p) => ['@type' => 'ListItem', 'position' => $p->position, 'name' => $p->entry->public_name, 'url' => route('bakkers.toon', $p->entry->company)])->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'</script>',
            'partner' => $edition ? $placements->provincePartner($edition, $province) : null,
            'pressRelease' => $edition ? PressRelease::query()->published()->where('edition_id', $edition->getKey())->where('province_id', $province->getKey())->orderByDesc('published_at')->first() : null,
        ]);
    }
}
