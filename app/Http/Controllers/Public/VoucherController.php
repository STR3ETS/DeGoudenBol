<?php

namespace App\Http\Controllers\Public;

use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Vouchers\Actions\ClaimVoucher;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Exceptions\VoucherException;
use App\Domain\Vouchers\Models\Voucher;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Models\VoucherWinner;
use App\Domain\Vouchers\Services\VoucherCode;
use App\Http\Controllers\Controller;
use App\Support\QrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Openbare kant van de cadeaubonnen: claimen, de bon zelf (/bon/{token}) en de winnaarspagina.
 * Geen persoonsgegevens op de controlepagina; rate limiting via de routes.
 */
class VoucherController extends Controller
{
    public function claim(VoucherCode $codes, string $token): View
    {
        $winner = $this->winnerByToken($codes, $token);
        $campaign = $winner->campaign()->with(['company', 'terms'])->firstOrFail();

        return view('public.cadeaubon.claim', [
            'winner' => $winner,
            'campaign' => $campaign,
            'terms' => $campaign->terms ?? TermsVersion::latestPublished(TermsType::VoucherCampaign),
            'token' => $token,
            'alreadyClaimed' => $winner->hasClaimed(),
            'expired' => $campaign->status === CampaignStatus::Expired || $campaign->last_redeem_day->endOfDay()->isPast(),
        ]);
    }

    public function store(Request $request, VoucherCode $codes, ClaimVoucher $claim, string $token): RedirectResponse
    {
        $winner = $this->winnerByToken($codes, $token);

        $data = $request->validate([
            'terms' => ['accepted'],
            'terms_version_id' => ['nullable', 'integer', 'exists:terms_versions,id'],
            'consent_public_name' => ['nullable', 'boolean'],
            'consent_photo' => ['nullable', 'boolean'],
        ], ['terms.accepted' => 'Accepteer de actievoorwaarden om de bon te claimen.']);

        try {
            $issued = $claim($winner, isset($data['terms_version_id']) ? (int) $data['terms_version_id'] : null, $request->boolean('consent_public_name'), $request->boolean('consent_photo'));
        } catch (VoucherException $exception) {
            return back()->withErrors(['terms' => $exception->getMessage()]);
        }

        return redirect()->route('bon.toon', $issued['token'])->with('status', 'Je cadeaubon is uitgegeven en per e-mail naar je toegestuurd.');
    }

    public function show(VoucherCode $codes, string $token): View
    {
        $voucher = Voucher::query()->where('qr_token_hash', $codes->hash($token))->with(['campaign.company.primaryLocation'])->firstOrFail();
        $status = $voucher->effectiveStatus();

        return view('public.cadeaubon.bon', [
            'voucher' => $voucher,
            'status' => $status,
            'company' => $voucher->campaign->company,
            'qr' => $status->value === 'issued' ? QrCode::svg(route('bon.toon', $token)) : null,
        ]);
    }

    public function winners(): View
    {
        $campaigns = VoucherCampaign::query()
            ->whereIn('status', [CampaignStatus::Open, CampaignStatus::Closed, CampaignStatus::Expired])
            ->with(['company', 'province', 'winners' => fn ($query) => $query->where('consent_public_name', true)->whereNotNull('claimed_at')->whereNull('anonymized_at')->orderBy('first_name')])
            ->get()
            ->sortBy(fn (VoucherCampaign $campaign) => [$campaign->province->sort, $campaign->company->name])
            ->values();

        return view('public.cadeaubon.winnaars', ['campaigns' => $campaigns]);
    }

    private function winnerByToken(VoucherCode $codes, string $token): VoucherWinner
    {
        return VoucherWinner::query()->where('claim_token_hash', $codes->hash($token))->firstOrFail();
    }
}
