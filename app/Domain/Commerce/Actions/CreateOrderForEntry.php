<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Settings\EditionSettings;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\Money;
use Carbon\CarbonInterface;

/**
 * Maakt de order voor een inschrijving: de pakketregel, bij de openingsactie een kortingsregel en
 * eventueel de extra test van de naturel oliebol. De omschrijving noemt de inhoud, nooit een positie.
 */
final class CreateOrderForEntry
{
    public const string OPENING_OFFER_LINE_TYPE = 'opening_offer';

    public const string PLAIN_ADDON_LINE_TYPE = 'plain_addon';

    public function __invoke(Entry $entry, Package $package, ParticipantUser $buyer, ?CarbonInterface $expiresAt = null): Order
    {
        $order = Order::query()->create([
            'edition_id' => $entry->edition_id,
            'orderable_type' => $entry->getMorphClass(),
            'orderable_id' => $entry->getKey(),
            'company_id' => $entry->company_id,
            'participant_user_id' => $buyer->getKey(),
            'status' => OrderStatus::Pending,
            'currency' => 'EUR',
            'subtotal_cents' => 0,
            'vat_cents' => 0,
            'total_cents' => 0,
            'expires_at' => $expiresAt,
        ]);

        foreach (self::lines($package, $entry->edition->settings, $entry->edition->year, $entry->rate, $entry->plain_addon) as $line) {
            $order->lines()->create([
                ...$line,
                'lineable_id' => $line['lineable_type'] === $package->getMorphClass() ? $package->getKey() : $entry->getKey(),
            ]);
        }

        $order->recalculateTotals();

        return $order->refresh();
    }

    /**
     * Orderregels voor een aanmelding, ook gebruikt om de prijs in de aanmeldwizard te tonen.
     *
     * @return list<array{lineable_type: string, description: string, quantity: int, unit_price_cents: int, vat_rate: float, counts_for_charity: bool, sort: int, subtotal_cents: int, vat_cents: int, total_cents: int}>
     */
    public static function lines(Package $package, EditionSettings $settings, int $year, EntryRate $rate, bool $plainAddon): array
    {
        $vatRate = (float) $package->vat_rate;
        $line = fn (string $type, string $description, int $cents, int $sort): array => [
            'lineable_type' => $type,
            'description' => $description,
            'quantity' => 1,
            'unit_price_cents' => $cents,
            'vat_rate' => $vatRate,
            'counts_for_charity' => true,
            'sort' => $sort,
            ...OrderLine::amounts(1, $cents, $vatRate),
        ];

        $lines = [$line($package->getMorphClass(), "{$package->name} – De Gouden Bol {$year}", $package->price_cents, 1)];

        if ($rate === EntryRate::OpeningOffer && $settings->openingOfferEnabled() && $settings->openingOfferCents < $package->price_cents) {
            $lines[] = $line(
                self::OPENING_OFFER_LINE_TYPE,
                'Openingsactie: deelname voor '.Money::format($settings->openingOfferCents).' in plaats van '.Money::format($package->price_cents),
                $settings->openingOfferCents - $package->price_cents,
                2,
            );
        }

        if ($plainAddon && $settings->plainAddonEnabled()) {
            $lines[] = $line(self::PLAIN_ADDON_LINE_TYPE, 'Extra test naturel oliebol (introductieprijs)', $settings->plainAddonCents, 3);
        }

        return $lines;
    }

    /**
     * @return array{subtotal_cents: int, total_cents: int}
     */
    public static function totals(Package $package, EditionSettings $settings, int $year, EntryRate $rate, bool $plainAddon): array
    {
        $lines = collect(self::lines($package, $settings, $year, $rate, $plainAddon));

        return ['subtotal_cents' => (int) $lines->sum('subtotal_cents'), 'total_cents' => (int) $lines->sum('total_cents')];
    }
}
