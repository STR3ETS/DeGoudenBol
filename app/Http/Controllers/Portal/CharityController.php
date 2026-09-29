<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Charities\Actions\NominateCharity;
use App\Domain\Charities\Exceptions\CharityException;
use App\Domain\Charities\Models\Charity;
use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Goed doel voordragen en de status volgen; toont ook wat er uit de eigen deelname is gereserveerd.
 */
class CharityController extends Controller
{
    public function index(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $edition = Edition::current();

        $charity = $edition
            ? Charity::query()->where('edition_id', $edition->getKey())->where('nominated_by_type', $company->getMorphClass())->where('nominated_by_id', $company->getKey())->with('province')->first()
            : null;

        $reserved = $edition
            ? (int) CharityReservation::query()->where('edition_id', $edition->getKey())->where('payer_type', $company->getMorphClass())->where('payer_id', $company->getKey())->sum('amount_cents')
            : 0;

        return view('portal.goed-doel', [
            'company' => $company,
            'edition' => $edition,
            'charity' => $charity,
            'reservedCents' => $reserved,
            'provinces' => Province::query()->orderBy('sort')->get(),
            'categories' => config('charities.categories'),
            'canNominate' => $access->roleOf($request->user('participant'), $company) === CompanyUserRole::Owner,
        ]);
    }

    public function store(Request $request, CompanyAccess $access, NominateCharity $nominate, string $bedrijf): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner]);
        $edition = Edition::current() ?? abort(404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'kvk_or_rsin' => ['nullable', 'string', 'max:20'],
            'is_anbi' => ['nullable', 'boolean'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'category' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('charities.categories')))],
            'motivation' => ['required', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
        ], [], ['name' => 'naam', 'kvk_or_rsin' => 'KvK- of RSIN-nummer', 'province_id' => 'provincie', 'category' => 'categorie', 'motivation' => 'motivatie', 'website' => 'website']);

        try {
            $nominate($company, $edition, [...$data, 'is_anbi' => $request->boolean('is_anbi')]);
        } catch (CharityException $exception) {
            return back()->withErrors(['name' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('portaal.goed-doel', $company)->with('status', 'Goed doel voorgedragen. Na beoordeling ziet u hier de status.');
    }
}
