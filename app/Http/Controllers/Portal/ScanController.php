<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Participants\Services\CompanyAccess;
use App\Domain\Vouchers\Actions\RedeemVoucher;
use App\Domain\Vouchers\Enums\RedemptionMethod;
use App\Domain\Vouchers\Models\Redemption;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Scan-PWA in het portaal (rollen eigenaar, medewerker en scanner): camera, handmatige invoer als noodroute,
 * resultaat in statuskleur, daarna optioneel een verzilverfoto (alleen met toestemming van de winnaar).
 */
class ScanController extends Controller
{
    public function show(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);

        return view('portal.scan', [
            'company' => $company,
            'outcome' => session('outcome'),
            'recent' => Redemption::query()->whereHas('campaign', fn ($q) => $q->where('company_id', $company->getKey()))->with('voucher.winner')->orderByDesc('created_at')->limit(8)->get(),
        ]);
    }

    public function redeem(Request $request, CompanyAccess $access, RedeemVoucher $redeem, string $bedrijf): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $data = $request->validate(['code' => ['required', 'string', 'max:200'], 'method' => ['nullable', 'in:scan,manual']]);

        $outcome = $redeem($company, $data['code'], RedemptionMethod::from($data['method'] ?? 'manual'), $request->user('participant'));

        return redirect()->route('portaal.scan', $company)->with('outcome', [
            'result' => $outcome->result,
            'title' => $outcome->title,
            'text' => $outcome->text,
            'chip' => $outcome->chip,
            'code' => $outcome->voucher?->code,
            'redemption' => $outcome->isRedeemed() ? $outcome->redemption?->getKey() : null,
            'photo_allowed' => $outcome->isRedeemed() && (bool) $outcome->voucher?->winner?->consent_photo,
        ]);
    }

    public function photo(Request $request, CompanyAccess $access, string $bedrijf, Redemption $redemption): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);

        abort_unless($redemption->wasRedeemed() && $redemption->campaign?->company_id === $company->getKey(), 403);
        abort_unless((bool) $redemption->voucher?->winner?->consent_photo, 403, 'De winnaar gaf geen toestemming voor een foto.');

        $data = $request->validate(['photo' => ['required', 'image', 'max:8192']]);
        $path = $data['photo']->store('redemptions', 'local');

        $redemption->forceFill(['photo_path' => $path])->save();

        return redirect()->route('portaal.scan', $company)->with('status', 'Verzilverfoto bewaard.');
    }
}
