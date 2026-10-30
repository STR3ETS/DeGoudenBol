<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Commerce\Enums\AvailabilityPhase;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Edition\Models\Edition;
use App\Support\Models\DomainModel;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product uit de sponsorcatalogus. Omschrijvingen noemen de inhoud, nooit een rangpositie.
 *
 * @property ProductCode $code
 * @property AvailabilityPhase $available_from_phase
 */
#[Fillable(['edition_id', 'code', 'name', 'description', 'price_cents', 'extra_link_price_cents', 'vat_rate', 'available_from_phase', 'is_custom', 'sort'])]
class Product extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => ProductCode::class,
            'price_cents' => 'integer',
            'extra_link_price_cents' => 'integer',
            'vat_rate' => 'float',
            'available_from_phase' => AvailabilityPhase::class,
            'is_custom' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_cents);
    }

    public function formattedExtraPrice(): ?string
    {
        return $this->extra_link_price_cents !== null ? Money::format($this->extra_link_price_cents) : null;
    }

    public function isCustomPriced(): bool
    {
        return $this->is_custom || $this->code->isCustomPriced();
    }

    /**
     * Sponsorpakketten uit de briefing van 29 okt (§8): prijzen excl. btw, beheerbaar in de backoffice.
     *
     * @return list<array{code: string, name: string, description: string, price_cents: int, extra_link_price_cents: int|null, vat_rate: float, available_from_phase: string, is_custom: bool, sort: int}>
     */
    public static function briefingCatalog(): array
    {
        $rows = [
            [ProductCode::Homepage, 'Basis sponsor', 'Vermelding met logo op de homepage, waar mogelijk met een link naar uw website.', 25000, null, AvailabilityPhase::Always, false, 10],
            [ProductCode::ParticipantLink, 'Deelnemerspartner', 'Vermelding bij één deelnemer die uw product of merk gebruikt: "Bakt met [uw merk]". De deelnemer bevestigt de koppeling zelf. Elke extra vermelding kost het lagere tarief.', 5000, 2500, AvailabilityPhase::Always, false, 20],
            [ProductCode::NationalTop, 'Top 10 partner', 'Vermelding bij één deelnemer in de landelijke Top 10. U reserveert vooraf; de koppeling volgt pas nadat de ranking openbaar is en de deelnemer akkoord geeft. Een plaats in de ranking wordt nooit gesuggereerd of gegarandeerd.', 45000, 7500, AvailabilityPhase::FinalistsKnown, false, 30],
            [ProductCode::ProvincePartner, 'Provinciale partner', 'Sponsoring verbonden aan één provincie, exclusief. Prijs en invulling op maat.', 0, null, AvailabilityPhase::Always, true, 40],
            [ProductCode::NationalPartner, 'Landelijke partner', 'Uitgebreide landelijke zichtbaarheid. Prijs en invulling op maat.', 0, null, AvailabilityPhase::Always, true, 50],
            [ProductCode::MainSponsor, 'Hoofdsponsor', 'Prominente samenwerking en landelijke presentatie. Prijs en invulling op maat.', 0, null, AvailabilityPhase::Always, true, 60],
            [ProductCode::Custom, 'Maatwerk (backoffice)', 'Eigen plaatsingen en periode voor afspraken buiten de vaste pakketten, zoals finale of goede doelen.', 0, null, AvailabilityPhase::Always, true, 90],
        ];

        return array_map(fn (array $row) => [
            'code' => $row[0]->value,
            'name' => $row[1],
            'description' => $row[2],
            'price_cents' => $row[3],
            'extra_link_price_cents' => $row[4],
            'vat_rate' => 21.0,
            'available_from_phase' => $row[5]->value,
            'is_custom' => $row[6],
            'sort' => $row[7],
        ], $rows);
    }
}
