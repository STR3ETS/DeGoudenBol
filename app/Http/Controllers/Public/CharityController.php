<?php

namespace App\Http\Controllers\Public;

use App\Domain\Charities\Services\CharityOverview;
use App\Domain\Commerce\Services\SponsorPlacements;
use App\Domain\Edition\Models\Edition;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Transparantie-overzicht per goed doel: grondslag, bedrag, selectie en uitbetaaldatum.
 */
class CharityController extends Controller
{
    public function __invoke(CharityOverview $overview, SponsorPlacements $placements): View
    {
        $edition = Edition::query()->current()->first();

        return view('public.goede-doelen', [
            'edition' => $edition,
            'overview' => $edition ? $overview->forEdition($edition) : null,
            'sponsors' => $edition ? $placements->charities($edition) : collect(),
        ]);
    }
}
