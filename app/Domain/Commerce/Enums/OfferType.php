<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Wat een sponsor kan aanbieden (briefing 29 okt §10).
 */
enum OfferType: string implements HasLabel
{
    case Voucher = 'voucher';
    case SamplePack = 'sample_pack';
    case ProductGift = 'product_gift';
    case Consult = 'consult';
    case WinnerPrize = 'winner_prize';

    public function getLabel(): string
    {
        return match ($this) {
            self::Voucher => 'Voucher',
            self::SamplePack => 'Proefpakket',
            self::ProductGift => 'Productcadeau',
            self::Consult => 'Adviesgesprek',
            self::WinnerPrize => 'Prijs voor winnaars',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])->all();
    }
}
