<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Services\CompanyAccess;
use App\Domain\Vouchers\Actions\AddWinner;
use App\Domain\Vouchers\Exceptions\VoucherException;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cadeaubonnenactie in het portaal: winnaars invoeren, status en rapportage.
 */
class CampaignController extends Controller
{
    public function index(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $campaign = $this->campaignFor($company->getKey());

        return view('portal.cadeaubonnen', [
            'company' => $company,
            'campaign' => $campaign,
            'winners' => $campaign?->winners()->with('voucher')->orderBy('created_at')->get() ?? collect(),
            'report' => $campaign?->report(),
            'canEdit' => in_array($access->roleOf($request->user('participant'), $company), [CompanyUserRole::Owner], true),
        ]);
    }

    public function storeWinner(Request $request, CompanyAccess $access, AddWinner $add, string $bedrijf): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner]);
        $campaign = $this->campaignFor($company->getKey()) ?? abort(404);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_initial' => ['required', 'string', 'max:4'],
            'email' => ['required', 'email', 'max:190'],
        ], [], ['first_name' => 'voornaam', 'last_initial' => 'eerste letter achternaam', 'email' => 'e-mailadres']);

        try {
            $add($campaign, $data['first_name'], $data['last_initial'], $data['email'], $request->user('participant'));
        } catch (VoucherException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('portaal.cadeaubonnen', $company)->with('status', 'Winnaar toegevoegd; de claimlink is verstuurd.');
    }

    private function campaignFor(int $companyId): ?VoucherCampaign
    {
        $edition = Edition::current();

        return $edition
            ? VoucherCampaign::query()->where('edition_id', $edition->getKey())->where('company_id', $companyId)->with(['province', 'edition'])->first()
            : null;
    }
}
