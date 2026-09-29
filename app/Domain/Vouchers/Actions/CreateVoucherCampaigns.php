<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Support\DutchTime;
use Carbon\CarbonImmutable;

/**
 * Na de bevriezing: automatisch een cadeaubonnenactie voor iedere Top 10-ondernemer (besluit 3).
 * Start op de hoofdpublicatie, winnaars invoeren tot de deadline, laatste verzilverdag uit de editie.
 */
final class CreateVoucherCampaigns
{
    public function __invoke(Edition $edition): int
    {
        $edition->loadMissing('provinces');
        $startsAt = $edition->main_publication_at ?? now();
        $deadline = $this->deadline($startsAt);
        $terms = TermsVersion::latestPublished(TermsType::VoucherCampaign);
        $listLength = $edition->settings->provincialListLength;
        $created = 0;

        foreach ($edition->provinces as $province) {
            $snapshot = RankingSnapshot::frozenForProvince($edition->getKey(), $province->getKey());

            if ($snapshot === null) {
                continue;
            }

            foreach ($snapshot->positions()->with('entry')->where('position', '<=', $listLength)->get() as $position) {
                $campaign = VoucherCampaign::query()->firstOrCreate(
                    ['edition_id' => $edition->getKey(), 'company_id' => $position->entry->company_id],
                    [
                        'entry_id' => $position->entry_id,
                        'province_id' => $province->getKey(),
                        'terms_version_id' => $terms?->getKey(),
                        'starts_at' => $startsAt,
                        'winners_deadline_at' => $deadline,
                        'last_redeem_day' => $edition->last_redeem_day ?? $startsAt->addDays(7)->toDateString(),
                        'winner_count' => $edition->settings->voucherCountPerWinner,
                        'voucher_value_cents' => $edition->settings->voucherValueCents,
                        'status' => CampaignStatus::Draft,
                    ],
                );

                if ($campaign->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return $created;
    }

    private function deadline(CarbonImmutable $startsAt): CarbonImmutable
    {
        $hours = (int) config('vouchers.winners_deadline_hours', 48);
        [$hour, $minute] = array_map('intval', explode(':', (string) config('vouchers.winners_deadline_time', '12:00').':00'));

        return DutchTime::display($startsAt)->addHours($hours)->setTime($hour, $minute)->utc();
    }
}
