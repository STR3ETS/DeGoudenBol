<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Exceptions\VoucherException;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Models\VoucherWinner;
use App\Domain\Vouchers\Notifications\VoucherClaimNotification;
use App\Domain\Vouchers\Services\VoucherCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Stap 1 van de flow: de ondernemer voert een winnaar in; het platform mailt een claimlink.
 * De keuze van winnaars blijft handwerk op social media.
 */
final class AddWinner
{
    public function __construct(
        private readonly VoucherCode $codes,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  bool  $asVoucherManager  Bonbeheer vult aan na de deadline (tekort); de ondernemer kan dat niet.
     */
    public function __invoke(VoucherCampaign $campaign, string $firstName, string $lastInitial, string $email, ?ParticipantUser $by = null, bool $asVoucherManager = false): VoucherWinner
    {
        if ($asVoucherManager) {
            if (in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Expired], true)) {
                throw new VoucherException('Deze actie is nog niet gestart of al afgelopen.');
            }
        } elseif (! $campaign->acceptsWinners()) {
            throw new VoucherException('Winnaars invoeren kan alleen zolang de actie open is en de deadline niet is verstreken.');
        }

        if ($campaign->remainingSlots() <= 0) {
            throw new VoucherException("Alle {$campaign->winner_count} winnaars zijn al ingevoerd.");
        }

        $email = Str::lower(trim($email));
        $campaign->loadMissing('edition');
        $max = $campaign->edition->settings->voucherMaxPerEmail;

        $existing = VoucherWinner::query()
            ->where('email', $email)
            ->whereHas('campaign', fn ($query) => $query->where('edition_id', $campaign->edition_id))
            ->count();

        if ($existing >= $max) {
            throw new VoucherException('Dit e-mailadres heeft deze editie al het maximum aantal bonnen ontvangen.');
        }

        $token = $this->codes->token();

        $winner = DB::transaction(function () use ($campaign, $firstName, $lastInitial, $email, $token): VoucherWinner {
            $winner = $campaign->winners()->create([
                'first_name' => trim($firstName),
                'last_initial' => Str::upper(mb_substr(trim($lastInitial), 0, 1)),
                'email' => $email,
                'claim_token_hash' => $token['hash'],
            ]);

            $this->audit->record('voucher_winner.added', $campaign, ['winner' => $winner->getKey()], null);

            return $winner;
        });

        Notification::route('mail', $email)->notify(new VoucherClaimNotification($winner, $token['plain']));

        return $winner;
    }
}
