<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Services\SponsorPlacements;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Services\OpeningStatus;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BakkerController extends Controller
{
    public function index(Request $request): View
    {
        $edition = Edition::query()->current()->first();
        $filters = $request->validate([
            'provincie' => ['nullable', 'string', 'exists:provinces,slug'],
            'type' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $entries = $edition
            ? Entry::query()
                ->where('edition_id', $edition->getKey())
                ->confirmed()
                ->with(['company.profile', 'company.primaryLocation', 'province', 'edition', 'publicationItems.batch'])
                ->when($filters['provincie'] ?? null, fn ($query, $slug) => $query->whereHas('province', fn ($q) => $q->where('slug', $slug)))
                ->when(CompanyType::tryFrom($filters['type'] ?? ''), fn ($query, $type) => $query->whereHas('company', fn ($q) => $q->where('type', $type)))
                ->when($filters['q'] ?? null, function ($query, string $term): void {
                    $query->where(function ($q) use ($term): void {
                        $q->where('public_name', 'like', "%{$term}%")
                            ->orWhereHas('company.primaryLocation', fn ($l) => $l->where('city', 'like', "%{$term}%"));
                    });
                })
                ->orderBy('public_name')
                ->paginate(24)
                ->withQueryString()
            : null;

        return view('public.bakkers.index', [
            'edition' => $edition,
            'entries' => $entries,
            'provinces' => Province::query()->orderBy('sort')->get(),
            'types' => CompanyType::options(),
            'filters' => $filters,
        ]);
    }

    public function show(Company $company, OpeningStatus $openingStatus, SponsorPlacements $placements): View
    {
        $edition = Edition::query()->current()->first();

        $entry = $edition
            ? $company->entries()->where('edition_id', $edition->getKey())->confirmed()->with(['province', 'publicationItems.batch', 'recognitions'])->first()
            : null;

        abort_if($entry === null, 404);

        $company->load(['profile', 'primaryLocation.openingHours', 'primaryLocation.openingHourExceptions']);
        $location = $company->primaryLocation;
        $total = $entry->publicTotal();

        return view('public.bakkers.show', [
            'edition' => $edition,
            'company' => $company,
            'entry' => $entry,
            'profile' => $company->profile,
            'location' => $location,
            'openNow' => $location ? $openingStatus->isOpenNow($location) : null,
            'total' => $total,
            'position' => $total !== null ? RankingSnapshot::latestForProvince($edition->getKey(), $entry->province_id)?->positions()->where('entry_id', $entry->getKey())->first() : null,
            'bakesWith' => $placements->bakesWith($entry),
        ]);
    }
}
