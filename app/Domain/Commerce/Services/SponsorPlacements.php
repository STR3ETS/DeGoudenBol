<?php

namespace App\Domain\Commerce\Services;

use App\Domain\Commerce\Models\Placement;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use Illuminate\Support\Collection;

/**
 * Leesmodel voor de publiekssite: welke sponsor staat waar. Alleen actieve plaatsingen binnen hun periode.
 */
final class SponsorPlacements
{
    /**
     * @return Collection<int, Placement>
     */
    public function homepage(Edition $edition): Collection
    {
        return $this->live($edition, ['national', 'homepage']);
    }

    public function provincePartner(Edition $edition, Province $province): ?Placement
    {
        return $this->live($edition, ["province:{$province->getKey()}"])->first();
    }

    /**
     * @return Collection<int, Placement>
     */
    public function nationalTop(Edition $edition): Collection
    {
        return $this->live($edition, ['national_top']);
    }

    /**
     * @return Collection<int, Placement>
     */
    public function finale(Edition $edition): Collection
    {
        return $this->live($edition, ['national', 'final', 'national_top']);
    }

    /**
     * @return Collection<int, Placement>
     */
    public function charities(Edition $edition): Collection
    {
        return $this->live($edition, ['national', 'charities']);
    }

    /**
     * "Bakt met": bevestigde koppelingen met een live plaatsing.
     *
     * @return Collection<int, Sponsor>
     */
    public function bakesWith(Entry $entry): Collection
    {
        return SponsorLink::query()
            ->where('edition_id', $entry->edition_id)
            ->where('company_id', $entry->company_id)
            ->whereNotNull('confirmed_by_company_at')
            ->whereNull('declined_at')
            ->whereHas('placement', fn ($query) => $query->live())
            ->with('sponsor')
            ->get()
            ->map(fn (SponsorLink $link) => $link->sponsor)
            ->filter(fn (?Sponsor $sponsor) => $sponsor?->status->value === 'active')
            ->values();
    }

    /**
     * Alle live plaatsingen van de editie, gegroepeerd per locatie (sponsorpagina).
     *
     * @return Collection<string, Collection<int, Placement>>
     */
    public function overview(Edition $edition): Collection
    {
        return Placement::query()
            ->where('edition_id', $edition->getKey())
            ->live()
            ->with(['sponsor', 'product', 'province', 'entry'])
            ->get()
            ->filter(fn (Placement $placement) => $placement->sponsor?->status->value === 'active')
            ->groupBy(fn (Placement $placement) => str_starts_with($placement->location, 'entry:') ? 'entry' : (str_starts_with($placement->location, 'province:') ? 'province' : $placement->location));
    }

    /**
     * @param  list<string>  $locations
     * @return Collection<int, Placement>
     */
    private function live(Edition $edition, array $locations): Collection
    {
        return Placement::query()
            ->where('edition_id', $edition->getKey())
            ->live()
            ->atLocations($locations)
            ->with(['sponsor', 'product'])
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (Placement $placement) => $placement->sponsor?->status->value === 'active')
            ->values();
    }
}
