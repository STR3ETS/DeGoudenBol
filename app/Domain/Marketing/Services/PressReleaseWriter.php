<?php

namespace App\Domain\Marketing\Services;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Models\RankingPosition;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Support\DutchTime;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Schrijft de eerste versie van een persbericht uit een bevroren (of landelijke) snapshot.
 * Platte tekst met alinea's; Communicatie redigeert daarna in de backoffice.
 */
final class PressReleaseWriter
{
    /**
     * @return array{title: string, body: string}
     */
    public function province(Edition $edition, Province $province, RankingSnapshot $snapshot, ?CarbonInterface $embargoUntil): array
    {
        $listLength = $edition->settings->provincialListLength;
        $positions = $snapshot->positions()->with('entry.company.primaryLocation')->where('position', '<=', $listLength)->get();
        $winner = $positions->first();
        $winnerEntry = $winner?->entry;
        $city = $winnerEntry?->company->primaryLocation?->city;
        $participants = Entry::query()->where('edition_id', $edition->getKey())->where('province_id', $province->getKey())->confirmed()->count();

        $title = $winnerEntry
            ? "{$winnerEntry->public_name} bakt de beste oliebol van {$province->name} {$edition->year}"
            : "De definitieve Top {$listLength} van {$province->name} {$edition->year}";

        $lead = $winnerEntry
            ? "{$winnerEntry->public_name}".($city ? " uit {$city}" : '')." bakt de beste oliebol van {$province->name}, volgens de blinde keuring van De Gouden Bol {$edition->year}. Het onafhankelijke panel gaf de oliebol een ".$this->score($winner).". Daarmee vertegenwoordigt {$winnerEntry->public_name} {$province->name} in de landelijke finale."
            : "De definitieve Top {$listLength} van {$province->name} is bekend.";

        $body = implode("\n\n", array_filter([
            $this->header($embargoUntil),
            $this->dateline($city ?? $province->name, $embargoUntil).' '.$lead,
            "De definitieve Top {$listLength} van {$province->name}:\n".$this->list($positions),
            "Over de keuring\n{$participants} ".($participants === 1 ? 'bakker of kraam' : 'bakkers en kramen')." uit {$province->name} ".($participants === 1 ? 'deed' : 'deden')." mee. Ieder monster is blind beoordeeld door minimaal {$edition->settings->minValidCards} panelleden op ".($edition->activeScoringModel?->criteria->count() ?? 8).' onderdelen van een 100-puntenmodel; het panel zag alleen testnummers. Geen cijfer is openbaar gemaakt zonder scorecontrole en goedkeuring door twee verschillende personen. Alle cijfers vanaf '.number_format($edition->settings->publishThreshold, 1, ',', '.').' staan op '.route('provincies.show', $province).'.',
            $this->about(),
            $this->note(route('provincies.show', $province)),
        ]));

        return ['title' => $title, 'body' => $body];
    }

    /**
     * @return array{title: string, body: string}
     */
    public function national(Edition $edition, RankingSnapshot $snapshot): array
    {
        $listLength = $edition->settings->nationalListLength;
        $positions = $snapshot->positions()->with(['entry.company.primaryLocation', 'entry.province'])->where('position', '<=', $listLength)->get();
        $winner = $positions->first();
        $winnerEntry = $winner?->entry;
        $city = $winnerEntry?->company->primaryLocation?->city;

        $title = $winnerEntry
            ? "{$winnerEntry->public_name} bakt de beste oliebol van Nederland {$edition->year}"
            : "De landelijke Top {$listLength} van De Gouden Bol {$edition->year}";

        $lead = $winnerEntry
            ? "{$winnerEntry->public_name}".($city ? " uit {$city}" : '')." ({$winnerEntry->province->name}) bakt de beste oliebol van Nederland. In de landelijke finale van De Gouden Bol {$edition->year}, een blinde keuring vanaf nul tussen de twaalf provinciewinnaars, gaf het panel de oliebol een ".$this->score($winner).'.'
            : "De landelijke lijst van De Gouden Bol {$edition->year} is bekend.";

        $body = implode("\n\n", array_filter([
            $this->header(null),
            $this->dateline($city ?? 'Nederland', null).' '.$lead,
            "De landelijke Top {$listLength}:\n".$this->list($positions, true),
            $this->about(),
            $this->note(route('finale')),
        ]));

        return ['title' => $title, 'body' => $body];
    }

    private function header(?CarbonInterface $embargoUntil): string
    {
        return $embargoUntil !== null && $embargoUntil->isFuture()
            ? 'PERSBERICHT – ONDER EMBARGO TOT '.strtoupper(DutchTime::format($embargoUntil, 'dddd D MMMM YYYY, HH:mm')).' UUR'
            : 'PERSBERICHT';
    }

    private function dateline(string $place, ?CarbonInterface $date): string
    {
        return "{$place}, ".DutchTime::date($date ?? now()).' –';
    }

    /**
     * @param  Collection<int, RankingPosition>  $positions
     */
    private function list(Collection $positions, bool $withProvince = false): string
    {
        return $positions->map(function (RankingPosition $position) use ($withProvince): string {
            $entry = $position->entry;
            $place = $entry->company->primaryLocation?->city;
            $suffix = $withProvince ? " ({$entry->province->name})" : '';

            return sprintf('%2d. %s%s%s – %s', $position->position, $entry->public_name, $place ? ", {$place}" : '', $suffix, $this->score($position));
        })->implode("\n");
    }

    private function score(?RankingPosition $position): string
    {
        return $position ? number_format((float) $position->total, 1, ',', '.') : '–';
    }

    private function about(): string
    {
        return "Over De Gouden Bol\nDe Gouden Bol is de landelijke oliebollenkeuring van Nederland. Bakkers en kramen melden zich per provincie aan en leveren hun oliebollen aan op een tijdslot; een onafhankelijk panel beoordeelt ze blind. De uitslag verschijnt per provincie op ".config('marketing.site_label').', met een verificatiepagina voor iedere erkenning.';
    }

    private function note(string $url): string
    {
        return "Noot voor de redactie (niet voor publicatie)\nBeeld, badges en cijfers per deelnemer: {$url}. Contact: ".config('press.contact_name').', '.config('press.contact_email').', '.config('press.contact_phone').'.';
    }
}
