<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Services\SponsorPlacements;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Sponsorpagina: partners en plaatsingen (gelabeld als sponsor) en de catalogus voor geïnteresseerden.
 */
class SponsorController extends Controller
{
    public function __invoke(SponsorPlacements $placements): View
    {
        $edition = Edition::query()->current()->first();
        $overview = $edition ? $placements->overview($edition) : collect();

        return view('public.sponsoren', [
            'edition' => $edition,
            'national' => $overview->get('national', collect()),
            'homepage' => $overview->get('homepage', collect()),
            'nationalTop' => $overview->get('national_top', collect()),
            'finale' => $overview->get('final', collect()),
            'charities' => $overview->get('charities', collect()),
            'provincePartners' => $overview->get('province', collect())->sortBy(fn ($placement) => $placement->province?->sort ?? 99)->values(),
            'participantLinks' => $overview->get('entry', collect())->count(),
            'products' => $edition ? Product::query()->where('edition_id', $edition->getKey())->orderBy('sort')->get() : collect(),
            'provinces' => Province::query()->orderBy('sort')->get(),
        ]);
    }
}
