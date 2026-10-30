<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Sponsorcatalogus (besluit 18): vaste producten tegen dossiertarieven, maatwerk alleen voor partners.
 */
enum ProductCode: string implements HasLabel
{
    case Homepage = 'homepage';
    case ParticipantLink = 'participant_link';
    case NationalTop = 'national_top';
    case ProvincePartner = 'province_partner';
    case NationalPartner = 'national_partner';
    case MainSponsor = 'main_sponsor';
    case Custom = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::Homepage => 'Homepage (logo met link)',
            self::ParticipantLink => 'Bij deelnemer ("Bakt met")',
            self::NationalTop => 'Landelijke toppositie',
            self::ProvincePartner => 'Provinciepartner (exclusief)',
            self::NationalPartner => 'Landelijke partner',
            self::MainSponsor => 'Hoofdsponsor',
            self::Custom => 'Maatwerk (partner, hoofdsponsor, finale, goede doelen)',
        };
    }

    public function requiresProvince(): bool
    {
        return $this === self::ProvincePartner;
    }

    public function requiresEntry(): bool
    {
        return $this === self::ParticipantLink;
    }

    public function isExclusiveByDefault(): bool
    {
        return $this === self::ProvincePartner;
    }

    public function hasExtraLinkPrice(): bool
    {
        return in_array($this, [self::ParticipantLink, self::NationalTop], true);
    }

    /**
     * Plaatsingslocatie zoals opgeslagen: `homepage`, `province:{id}`, `entry:{id}`, `national_top`, `final`, `national`, `charities`.
     */
    public function location(?int $provinceId = null, ?int $entryId = null, ?string $custom = null): string
    {
        return match ($this) {
            self::Homepage => 'homepage',
            self::ParticipantLink => "entry:{$entryId}",
            self::NationalTop => 'national_top',
            self::ProvincePartner => "province:{$provinceId}",
            self::NationalPartner, self::MainSponsor => 'national',
            self::Custom => $custom === 'province' ? "province:{$provinceId}" : ($custom ?? 'national'),
        };
    }

    /**
     * Pakketten op de sponsorpagina (briefing 29 okt §8). Het algemene maatwerkproduct blijft voor de backoffice.
     */
    public function onWebsite(): bool
    {
        return $this !== self::Custom;
    }

    /**
     * Vaste pakketten reken je direct af; maatwerk is een aanvraag zonder prijs of betaalverplichting.
     */
    public function isCustomPriced(): bool
    {
        return in_array($this, [self::ProvincePartner, self::NationalPartner, self::MainSponsor, self::Custom], true);
    }

    /**
     * @return array<string, string>
     */
    public static function customLocations(): array
    {
        return [
            'national' => 'Landelijk partner / hoofdsponsor (homepage, finale, sponsorpagina)',
            'final' => 'Finale',
            'charities' => 'Goede doelen',
            'homepage' => 'Homepage',
            'province' => 'Provincie (exclusief als provinciepartner)',
        ];
    }
}
