<?php

namespace App\Domain\Edition\Enums;

use Filament\Support\Contracts\HasLabel;

enum TermsType: string implements HasLabel
{
    case Participation = 'participation';
    case VoucherCampaign = 'voucher_campaign';
    case Privacy = 'privacy';
    case ImageConsent = 'image_consent';

    public function getLabel(): string
    {
        return match ($this) {
            self::Participation => 'Deelnamevoorwaarden',
            self::VoucherCampaign => 'Actievoorwaarden cadeaubonnen',
            self::Privacy => 'Privacyverklaring',
            self::ImageConsent => 'Beeldtoestemming',
        };
    }
}
