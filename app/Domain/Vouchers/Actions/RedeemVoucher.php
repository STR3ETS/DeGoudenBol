<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Vouchers\Data\RedemptionOutcome;
use App\Domain\Vouchers\Enums\RedemptionMethod;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Domain\Vouchers\Models\Redemption;
use App\Domain\Vouchers\Models\Voucher;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Services\VoucherCode;
use App\Support\DutchTime;

/**
 * Stap 4: verzilveren, alleen in het portaal van de uitgevende ondernemer. Eén atomaire bewerking:
 * UPDATE … WHERE status = 'issued' AND campaign van dit bedrijf → 0 rijen = al gebruikt/verlopen.
 * Twee telefoons tegelijk kunnen nooit dubbel verzilveren.
 */
final class RedeemVoucher
{
    public function __construct(private readonly VoucherCode $codes) {}

    public function __invoke(Company $company, string $input, RedemptionMethod $method, ?ParticipantUser $by = null): RedemptionOutcome
    {
        $voucher = $this->find($input);
        $campaignIds = VoucherCampaign::query()->where('company_id', $company->getKey())->pluck('id')->all();

        if ($voucher === null) {
            return $this->refuse(null, $company, $method, $by, 'unknown', 'Onbekende bon', 'Deze code of QR hoort niet bij een uitgegeven cadeaubon. Controleer het bonnummer.', 'fout');
        }

        if (! in_array($voucher->voucher_campaign_id, $campaignIds, true)) {
            return $this->refuse($voucher, $company, $method, $by, 'wrong_company', 'Bon van een andere ondernemer', 'Deze bon is uitgegeven door een ander bedrijf en kan hier niet worden verzilverd.', 'fout');
        }

        $now = now();

        $updated = Voucher::query()
            ->whereKey($voucher->getKey())
            ->where('status', VoucherStatus::Issued)
            ->where('expires_at', '>', $now)
            ->whereIn('voucher_campaign_id', $campaignIds)
            ->update(['status' => VoucherStatus::Redeemed->value, 'redeemed_at' => $now, 'updated_at' => $now]);

        if ($updated === 1) {
            $voucher->refresh()->loadMissing('winner');

            $redemption = Redemption::query()->create([
                'voucher_id' => $voucher->getKey(),
                'voucher_campaign_id' => $voucher->voucher_campaign_id,
                'participant_user_id' => $by?->getKey(),
                'result' => 'redeemed',
                'method' => $method,
            ]);

            return new RedemptionOutcome('redeemed', 'Verzilverd', "{$voucher->winner->first_name} · {$voucher->formattedValue()} · geldig t/m ".DutchTime::date($voucher->expires_at), 'succes', $voucher, $redemption);
        }

        $voucher->refresh();

        return match ($voucher->effectiveStatus()) {
            VoucherStatus::Redeemed => $this->refuse($voucher, $company, $method, $by, 'already_redeemed', 'Al gebruikt', 'Deze bon is al verzilverd op '.DutchTime::format($voucher->redeemed_at, 'D MMMM, HH:mm').'.', 'waarschuwing'),
            VoucherStatus::Expired => $this->refuse($voucher, $company, $method, $by, 'expired', 'Verlopen', 'Deze bon was geldig t/m '.DutchTime::date($voucher->expires_at).'.', 'neutraal'),
            default => $this->refuse($voucher, $company, $method, $by, 'void', 'Ongeldig', 'Deze bon is ongeldig gemaakt.', 'fout'),
        };
    }

    private function find(string $input): ?Voucher
    {
        $token = $this->codes->tokenFromScan($input);

        if ($token !== null) {
            $byToken = Voucher::query()->where('qr_token_hash', $this->codes->hash($token))->first();

            if ($byToken !== null) {
                return $byToken;
            }
        }

        $code = $this->codes->normalizeCode($input);

        return $code !== null ? Voucher::query()->where('code', $code)->first() : null;
    }

    private function refuse(?Voucher $voucher, Company $company, RedemptionMethod $method, ?ParticipantUser $by, string $reason, string $title, string $text, string $chip): RedemptionOutcome
    {
        $redemption = Redemption::query()->create([
            'voucher_id' => $voucher?->getKey(),
            'voucher_campaign_id' => $voucher?->voucher_campaign_id ?? VoucherCampaign::query()->where('company_id', $company->getKey())->value('id'),
            'participant_user_id' => $by?->getKey(),
            'result' => 'refused',
            'method' => $method,
            'refusal_reason' => $reason,
        ]);

        return new RedemptionOutcome($reason, $title, $text, $chip, $voucher, $redemption);
    }
}
