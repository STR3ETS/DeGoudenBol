<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Actions\SubmitDiscountProof;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Stap 1 in het portaal (briefing 29 okt §4): aanmelddatum, tarief, kortingsstatus, betaalstatus en
 * de factuur. Hier levert de bakker ook het bewijs voor de kortingsactie aan.
 */
class RegistrationController extends Controller
{
    public function show(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $edition = Edition::current();
        $entry = $edition
            ? $company->entries()->where('edition_id', $edition->getKey())->with(['province', 'package', 'order.lines', 'order.invoice', 'order.latestPayment', 'pickupRequest.order.invoice'])->first()
            : null;

        return view('portal.aanmelding', [
            'company' => $company,
            'edition' => $edition,
            'entry' => $entry,
            'canDecide' => $access->roleOf($request->user('participant'), $company) === CompanyUserRole::Owner,
        ]);
    }

    public function discountProof(Request $request, CompanyAccess $access, SubmitDiscountProof $submit, string $bedrijf): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner]);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->first() : null;

        abort_if($entry === null, 404);

        $data = $request->validate([
            'link' => ['nullable', 'url', 'max:500', 'required_without:bewijs'],
            'bewijs' => ['nullable', 'image', 'max:5120'],
        ], [
            'link.required_without' => 'Vul de link naar uw post in of voeg een schermafbeelding toe.',
        ], ['link' => 'link naar je post', 'bewijs' => 'schermafbeelding']);

        try {
            $submit($entry, $data['link'] ?? null, $request->file('bewijs'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['link' => $exception->getMessage()]);
        }

        return redirect()->route('portaal.aanmelding', $company)->with('status', 'Bedankt! We bekijken je post en mailen je zodra de korting is goedgekeurd.');
    }
}
