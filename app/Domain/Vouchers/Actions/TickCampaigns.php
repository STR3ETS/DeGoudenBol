<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Domain\Vouchers\Models\Voucher;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Domain\Vouchers\Notifications\CampaignOpenedNotification;
use App\Support\DutchTime;
use Carbon\CarbonInterface;

/**
 * Tijdpad (docs/04 §8): start op de publicatiedag, winnaarsdeadline, laatste verzilverdag.
 * Draait ieder uur: opent, sluit (met automatisch tekort) en laat vervallen.
 *
 * @return array{opened: int, closed: int, expired_campaigns: int, expired_vouchers: int}
 */
final class TickCampaigns
{
    public function __construct(private readonly RecordShortfall $recordShortfall) {}

    /**
     * @return array{opened: int, closed: int, expired_campaigns: int, expired_vouchers: int}
     */
    public function __invoke(?CarbonInterface $now = null): array
    {
        $now ??= now();
        $result = ['opened' => 0, 'closed' => 0, 'expired_campaigns' => 0, 'expired_vouchers' => 0];

        foreach (VoucherCampaign::query()->where('status', CampaignStatus::Draft)->where('starts_at', '<=', $now)->with('company.users')->get() as $campaign) {
            $campaign->forceFill(['status' => CampaignStatus::Open, 'opened_notified_at' => $now])->save();

            $campaign->company->users
                ->filter(fn ($user) => $user->pivot->role === CompanyUserRole::Owner)
                ->each->notify(new CampaignOpenedNotification($campaign));

            $result['opened']++;
        }

        foreach (VoucherCampaign::query()->where('status', CampaignStatus::Open)->where('winners_deadline_at', '<=', $now)->get() as $campaign) {
            $campaign->forceFill(['status' => CampaignStatus::Closed])->save();

            $missing = $campaign->winner_count - $campaign->winners()->count();

            if ($missing > 0 && $campaign->shortfalls()->doesntExist()) {
                ($this->recordShortfall)($campaign, $missing, 'Automatisch vastgesteld bij de winnaarsdeadline', null);
            }

            $result['closed']++;
        }

        $result['expired_vouchers'] = Voucher::query()
            ->where('status', VoucherStatus::Issued)
            ->where('expires_at', '<', $now)
            ->update(['status' => VoucherStatus::Expired->value, 'updated_at' => $now]);

        $result['expired_campaigns'] = VoucherCampaign::query()
            ->whereIn('status', [CampaignStatus::Open, CampaignStatus::Closed])
            ->where('last_redeem_day', '<', DutchTime::display($now)->toDateString())
            ->update(['status' => CampaignStatus::Expired->value, 'updated_at' => $now]);

        return $result;
    }
}
