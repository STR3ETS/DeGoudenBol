<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Commerce\Actions\ConfirmSponsorLink;
use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Commerce\Models\SponsorOffer;
use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Bakt met [sponsor]": de deelnemer bevestigt of weigert een sponsorkoppeling.
 */
class SponsorLinkController extends Controller
{
    public function index(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->with('recognitions')->first() : null;
        $recognitions = $entry?->recognitions->pluck('type')->map(fn ($type) => $type instanceof BackedEnum ? $type->value : $type)->all() ?? [];

        // Goedgekeurde sponsoracties voor deze deelnemer: voor iedereen, of pas na de uitslag voor Top 10 en winnaars.
        $offers = $edition && $entry?->status->occupiesPlace()
            ? SponsorOffer::query()->where('edition_id', $edition->getKey())->visible()->with('sponsor')->get()
                ->filter(fn (SponsorOffer $offer) => $offer->audience->requiredRecognition() === null || in_array($offer->audience->requiredRecognition()->value, $recognitions, true))
                ->values()
            : collect();

        return view('portal.sponsoren', [
            'offers' => $offers,
            'company' => $company,
            'links' => $edition
                ? SponsorLink::query()->where('edition_id', $edition->getKey())->where('company_id', $company->getKey())->with(['sponsor', 'placement'])->orderBy('requested_at')->get()
                : collect(),
            'canDecide' => $access->roleOf($request->user('participant'), $company) === CompanyUserRole::Owner,
        ]);
    }

    public function respond(Request $request, CompanyAccess $access, ConfirmSponsorLink $confirm, string $bedrijf, SponsorLink $link): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner]);

        abort_unless($link->company_id === $company->getKey(), 404);

        $data = $request->validate(['beslissing' => ['required', 'in:bevestigen,weigeren']]);
        $confirm($link, $data['beslissing'] === 'bevestigen');

        return redirect()->route('portaal.sponsoren', $company)->with('status', $data['beslissing'] === 'bevestigen' ? 'Koppeling bevestigd; uw profiel toont "Bakt met '.$link->sponsor->name.'".' : 'Koppeling geweigerd.');
    }
}
