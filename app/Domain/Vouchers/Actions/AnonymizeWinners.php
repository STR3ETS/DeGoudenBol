<?php

namespace App\Domain\Vouchers\Actions;

use App\Domain\Vouchers\Models\VoucherWinner;
use App\Support\DutchTime;
use Carbon\CarbonImmutable;

/**
 * Winnaarsgegevens minimaal en tijdelijk: na de ingestelde datum blijven alleen de aantallen over.
 */
final class AnonymizeWinners
{
    public function __invoke(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::now();
        $from = CarbonImmutable::parse((string) config('vouchers.anonymise_from'), DutchTime::zone());

        if ($today->lessThan($from)) {
            return 0;
        }

        $count = 0;

        VoucherWinner::query()->whereNull('anonymized_at')->whereHas('campaign', fn ($query) => $query->where('last_redeem_day', '<', $today->toDateString()))
            ->each(function (VoucherWinner $winner) use (&$count): void {
                $winner->forceFill([
                    'first_name' => 'Winnaar',
                    'last_initial' => '',
                    'email' => 'geanonimiseerd-'.$winner->getKey().'@invalid.local',
                    'anonymized_at' => now(),
                ])->save();

                $count++;
            });

        return $count;
    }
}
