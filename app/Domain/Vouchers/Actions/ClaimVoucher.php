<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Domain\Vouchers\Exceptions\VoucherException;
use App\Domain\Vouchers\Models\Voucher;
use App\Domain\Vouchers\Models\VoucherWinner;
use App\Domain\Vouchers\Notifications\VoucherIssuedNotification;
use App\Domain\Vouchers\Services\VoucherCode;
use App\Support\DutchTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Stap 2 en 3: de winnaar bevestigt, accepteert de actievoorwaarden (versie vastgelegd) en geeft
 * eventueel toestemming; het platform geeft de digitale bon uit en mailt de link.
 *
 * @return array{voucher: Voucher, token: string}
 */
final class ClaimVoucher
{
    public function __construct(
        private readonly VoucherCode $codes,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{voucher: Voucher, token: string}
     */
    public function __invoke(VoucherWinner $winner, ?int $termsVersionId, bool $consentPublicName, bool $consentPhoto): array
    {
        if ($winner->hasClaimed()) {
            throw new VoucherException('Deze bon is al geclaimd.');
        }

        $campaign = $winner->campaign()->with(['province', 'edition'])->firstOrFail();

        if ($campaign->status === CampaignStatus::Expired || $campaign->last_redeem_day->endOfDay()->isPast()) {
            throw new VoucherException('De actie is afgelopen; deze bon kan niet meer worden geclaimd.');
        }

        $token = $this->codes->token();
        $expiresAt = DutchTime::display($campaign->last_redeem_day)->endOfDay()->utc();

        $voucher = DB::transaction(function () use ($winner, $campaign, $token, $expiresAt, $termsVersionId, $consentPublicName, $consentPhoto): Voucher {
            $winner->forceFill([
                'claimed_at' => now(),
                'terms_version_id' => $termsVersionId ?? $campaign->terms_version_id,
                'consent_public_name' => $consentPublicName,
                'consent_photo' => $consentPhoto,
            ])->save();

            $voucher = Voucher::query()->create([
                'code' => $this->codes->generate($campaign->province, $campaign->edition->year),
                'qr_token_hash' => $token['hash'],
                'voucher_campaign_id' => $campaign->getKey(),
                'voucher_winner_id' => $winner->getKey(),
                'value_cents' => $campaign->voucher_value_cents,
                'status' => VoucherStatus::Issued,
                'issued_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            $this->audit->record('voucher.issued', $voucher, ['code' => $voucher->code], null);

            return $voucher;
        });

        Notification::route('mail', $winner->email)->notify(new VoucherIssuedNotification($voucher, $token['plain']));

        return ['voucher' => $voucher, 'token' => $token['plain']];
    }
}
