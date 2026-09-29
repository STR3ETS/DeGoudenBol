<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\AvailabilityPhase;
use App\Domain\Commerce\Enums\PlacementStatus;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Exceptions\SponsoringException;
use App\Domain\Commerce\Models\Placement;
use App\Domain\Commerce\Models\Product;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Notifications\SponsorLinkRequestedNotification;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Models\Finalist;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Plaatsing reserveren: verkoopfase, exclusiviteit (het systeem voorkomt dubbele verkoop) en prijs
 * (eerste koppeling vol tarief, extra koppelingen het lagere tarief). Een koppeling bij een deelnemer
 * vraagt de deelnemer om bevestiging ("Bakt met").
 */
final class CreatePlacement
{
    /**
     * @param  array{province_id?: int|string|null, entry_id?: int|string|null, label?: string|null, location?: string|null, starts_at: mixed, ends_at: mixed, is_exclusive?: bool}  $data
     */
    public function __invoke(Sponsor $sponsor, Product $product, array $data): Placement
    {
        $product->loadMissing('edition');
        $edition = $product->edition;
        $code = $product->code;

        if ($product->available_from_phase === AvailabilityPhase::FinalistsKnown && ! Finalist::query()->where('edition_id', $edition->getKey())->exists()) {
            throw new SponsoringException("{$product->name} is pas te koop zodra de finalisten bekend zijn.");
        }

        $province = filled($data['province_id'] ?? null) ? Province::query()->findOrFail((int) $data['province_id']) : null;
        $entry = filled($data['entry_id'] ?? null) ? Entry::query()->where('edition_id', $edition->getKey())->confirmed()->findOrFail((int) $data['entry_id']) : null;

        if ($code->requiresProvince() && $province === null) {
            throw new SponsoringException('Kies een provincie voor deze plaatsing.');
        }

        if ($code->requiresEntry() && $entry === null) {
            throw new SponsoringException('Kies een bevestigde deelnemer voor deze koppeling.');
        }

        $custom = $data['location'] ?? null;

        if ($code === ProductCode::Custom && $custom === 'province' && $province === null) {
            throw new SponsoringException('Kies een provincie voor deze maatwerkplaatsing.');
        }

        $location = $code->location($province?->getKey(), $entry?->getKey(), $custom);
        $startsAt = CarbonImmutable::parse((string) $data['starts_at']);
        $endsAt = CarbonImmutable::parse((string) $data['ends_at']);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new SponsoringException('De einddatum moet na de startdatum liggen.');
        }

        $isExclusive = $code->isExclusiveByDefault() || (bool) ($data['is_exclusive'] ?? false) || ($code === ProductCode::Custom && $custom === 'province');

        return DB::transaction(function () use ($sponsor, $product, $edition, $code, $location, $province, $entry, $startsAt, $endsAt, $isExclusive, $data): Placement {
            $overlapping = Placement::query()
                ->lockForUpdate()
                ->where('edition_id', $edition->getKey())
                ->where('location', $location)
                ->whereIn('status', [PlacementStatus::Draft, PlacementStatus::Active])
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->with('sponsor')
                ->get();

            $conflict = $overlapping->first(fn (Placement $other) => $isExclusive || $other->is_exclusive);

            if ($conflict !== null) {
                $label = $province ? "Provinciepartner {$province->name}" : 'Deze exclusieve plaatsing';

                throw new SponsoringException("{$label} is in deze periode al vergeven aan {$conflict->sponsor->name}; dubbele verkoop is geblokkeerd.");
            }

            $price = $product->price_cents;

            if ($code->hasExtraLinkPrice() && $product->extra_link_price_cents !== null) {
                $hasFirst = Placement::query()
                    ->where('edition_id', $edition->getKey())
                    ->where('sponsor_id', $sponsor->getKey())
                    ->where('product_id', $product->getKey())
                    ->whereIn('status', [PlacementStatus::Draft, PlacementStatus::Active])
                    ->exists();

                if ($hasFirst) {
                    $price = $product->extra_link_price_cents;
                }
            }

            $placement = Placement::query()->create([
                'edition_id' => $edition->getKey(),
                'sponsor_id' => $sponsor->getKey(),
                'product_id' => $product->getKey(),
                'location' => $location,
                'province_id' => $province?->getKey(),
                'entry_id' => $entry?->getKey(),
                'label' => $data['label'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'is_exclusive' => $isExclusive,
                'status' => PlacementStatus::Draft,
                'price_cents' => $price,
            ]);

            if ($entry !== null) {
                $link = $sponsor->links()->firstOrCreate(
                    ['edition_id' => $edition->getKey(), 'company_id' => $entry->company_id],
                    ['placement_id' => $placement->getKey(), 'requested_at' => now()],
                );

                if ($link->wasRecentlyCreated) {
                    $entry->company->users
                        ->filter(fn ($user) => $user->pivot->role === CompanyUserRole::Owner)
                        ->each->notify(new SponsorLinkRequestedNotification($link));
                }
            }

            return $placement;
        });
    }
}
