<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Services\SponsorPlacements;
use App\Domain\Edition\Models\Edition;
use App\Http\Controllers\Controller;
use App\Support\DutchTime;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    private const array DAY_LABELS = [
        'monday' => 'maandag',
        'tuesday' => 'dinsdag',
        'wednesday' => 'woensdag',
        'thursday' => 'donderdag',
        'friday' => 'vrijdag',
        'saturday' => 'zaterdag',
        'sunday' => 'zondag',
    ];

    public function __invoke(SponsorPlacements $placements): View
    {
        $edition = Edition::query()->current()->with(['provinces', 'activeScoringModel.criteria'])->first();
        $settings = $edition?->settings;
        $criteria = $edition?->activeScoringModel?->criteria ?? collect();

        return view('public.home', [
            'edition' => $edition,
            'settings' => $settings,
            'criteria' => $criteria,
            'provinces' => $edition?->provinces ?? collect(),
            'opensAt' => $edition?->registration_opens_at ? DutchTime::format($edition->registration_opens_at, 'D MMMM YYYY') : null,
            'opensAtLong' => $edition?->registration_opens_at ? DutchTime::format($edition->registration_opens_at, 'dddd D MMMM YYYY') : null,
            'firstTestDay' => DutchTime::date($edition?->first_test_day),
            'lastTestDay' => DutchTime::date($edition?->last_test_day),
            'publicationDate' => $edition?->main_publication_at ? DutchTime::format($edition->main_publication_at, 'dddd D MMMM YYYY') : null,
            'publicationShort' => $edition?->main_publication_at ? DutchTime::format($edition->main_publication_at, 'D MMM') : null,
            'publicationMoment' => $settings ? $this->publicationMoment($settings->publicationDays, $settings->publicationTime) : null,
            'threshold' => number_format($settings?->publishThreshold ?? 5.0, 1, ',', '.'),
            'placeholders' => (bool) config('site.placeholders'),
            'sponsors' => $edition ? $placements->homepage($edition) : collect(),
        ]);
    }

    /**
     * @param  list<string>  $days
     */
    private function publicationMoment(array $days, string $time): string
    {
        $labels = array_values(array_filter(array_map(fn (string $day) => self::DAY_LABELS[$day] ?? null, $days)));

        if ($labels === []) {
            return "om {$time} uur";
        }

        $last = array_pop($labels);
        $dayText = $labels === [] ? $last : implode(', ', $labels).' en '.$last;

        return "{$dayText} om {$time} uur";
    }
}
