@php
    use App\Domain\Participants\Models\OpeningHour;
    use App\Support\DutchTime;

    $year = $edition?->year ?? now()->year;
    $story = $profile?->publishedStory();
    $tagline = $profile?->publishedTagline() ?? $entry->tagline;
    $specialties = $profile?->publishedSpecialties() ?? [];
    $today = DutchTime::display(now())->isoWeekday();
    $socials = $company->socials ?? [];
@endphp
<x-layouts.public :title="$entry->public_name" :description="$tagline ?? $entry->public_name.' doet mee aan De Gouden Bol '.$year.' in '.$entry->province->name.'.'">
    <x-slot:head>
        <x-ui.json-ld :data="array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Bakery',
            'name' => $entry->public_name,
            'url' => route('bakkers.toon', $company),
            'description' => $tagline,
            'address' => $location ? [
                '@type' => 'PostalAddress',
                'streetAddress' => trim($location->street.' '.$location->house_number),
                'postalCode' => $location->postcode,
                'addressLocality' => $location->city,
                'addressRegion' => $entry->province->name,
                'addressCountry' => 'NL',
            ] : null,
            'geo' => $location?->lat ? ['@type' => 'GeoCoordinates', 'latitude' => $location->lat, 'longitude' => $location->lng] : null,
            'openingHoursSpecification' => $location?->openingHours->reject(fn ($h) => $h->is_closed)->map(fn ($h) => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$h->weekday - 1],
                'opens' => substr($h->opens_at, 0, 5),
                'closes' => substr($h->closes_at, 0, 5),
            ])->values()->all() ?: null,
            'sameAs' => array_values(array_filter([$company->website])),
        ])" />
        <x-ui.json-ld :data="[
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Alle bakkers', 'item' => route('bakkers.index')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $entry->public_name, 'item' => route('bakkers.toon', $company)],
            ],
        ]" />
    </x-slot:head>

    {{-- Profiel-hero --}}
    <section class="relative h-[60vh] min-h-[460px] overflow-hidden">
        <x-ui.placeholder-image label="Foto van de bakkerij volgt" class="h-full !rounded-none" />
        <div class="absolute inset-0 bg-gradient-to-t from-espresso/85 via-espresso/20 to-transparent"></div>
        <div class="absolute right-0 bottom-0 left-0 p-6 md:p-12">
            <div class="site-container op-donker flex flex-wrap items-end justify-between gap-6 !px-0">
                <div>
                    <nav class="mb-3 text-[0.7rem] font-bold tracking-[0.14em] text-room-meta uppercase" aria-label="Kruimelpad">
                        <a href="{{ route('bakkers.index') }}" class="hover:text-goud">&larr; Alle bakkers</a> &nbsp;/&nbsp; {{ $entry->public_name }}
                    </nav>
                    <h1 class="kop-pagina mb-3 !text-room">{{ $entry->public_name }}</h1>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($total !== null)
                            <x-ui.rank-pill :score="$total" :top="($position?->position ?? 0) === 1" />
                        @else
                            <x-ui.chip status="glas">&#9733; Deelnemer {{ $year }}</x-ui.chip>
                        @endif
                        <span class="text-[0.9rem] text-room-zacht">&#9679; {{ $location?->city }} &middot; {{ $entry->province->name }}</span>
                        @if ($openNow === true)
                            <x-ui.chip status="succes" :dot="true">Nu open</x-ui.chip>
                        @elseif ($openNow === false)
                            <x-ui.chip status="neutraal" :dot="true">Nu gesloten</x-ui.chip>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section--sub py-14">
        <div class="site-container grid gap-12 lg:grid-cols-[1fr_360px]">
            <div>
                <div class="mb-12">
                    <x-ui.eyebrow>Over {{ $entry->public_name }}</x-ui.eyebrow>
                    @if ($tagline)
                        <h2 class="kop-2 mb-5">{{ $tagline }}</h2>
                    @endif
                    @if ($story)
                        <div class="body-text flex flex-col gap-4">
                            @foreach (preg_split('/\R{2,}/', $story) as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    @else
                        <p class="body-text">Het verhaal van deze bakker volgt zodra het door de redactie is goedgekeurd.</p>
                    @endif
                    @if ($specialties)
                        <ul class="mt-6 flex flex-wrap gap-2" aria-label="Specialiteiten">
                            @foreach ($specialties as $specialty)
                                <li><x-ui.chip status="goud">{{ $specialty }}</x-ui.chip></li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <x-ui.card variant="zand">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.eyebrow>De Gouden Bol {{ $year }}</x-ui.eyebrow>
                            <h3 class="kop-3">Keuringsresultaat</h3>
                        </div>
                        @if ($total !== null)
                            <x-ui.chip status="succes" :dot="true">Officieel getest</x-ui.chip>
                        @else
                            <x-ui.chip status="neutraal">Nog niet beoordeeld</x-ui.chip>
                        @endif
                    </div>
                    @if ($total !== null)
                        <div class="flex flex-wrap items-end gap-6">
                            <div>
                                <div class="text-[0.65rem] font-bold tracking-[0.18em] text-gedempt uppercase">Cijfer van het panel</div>
                                <div class="cijfer font-kop text-[3.6rem] leading-none font-bold text-espresso">{{ number_format($total, 1, ',', '.') }}</div>
                            </div>
                            @if ($position)
                                <div class="pb-2">
                                    <div class="text-[0.65rem] font-bold tracking-[0.18em] text-gedempt uppercase">Voorlijst {{ $entry->province->name }}</div>
                                    <div class="font-kop text-[1.8rem] leading-none font-semibold text-espresso">Plaats {{ $position->position }}</div>
                                </div>
                            @endif
                        </div>
                        <p class="body-text mt-4 text-[0.9rem]">Blind beoordeeld door het panel van De Gouden Bol op de acht onderdelen van het beoordelingsmodel. <a href="{{ route('provincies.show', $entry->province) }}" class="font-bold text-goud-tekst">Bekijk de Voorlijst van {{ $entry->province->name }}</a>.</p>
                        @php $activeRecognitions = $entry->recognitions->filter(fn ($r) => $r->isActive() && $r->type->value !== 'participant'); @endphp
                        @if ($activeRecognitions->isNotEmpty())
                            <ul class="mt-5 flex flex-wrap gap-2" aria-label="Erkenningen">
                                @foreach ($activeRecognitions as $recognition)
                                    <li><a href="{{ $recognition->verificationUrl() }}" class="no-underline"><x-ui.chip status="goud">&#9733; {{ $recognition->type->getLabel() }}</x-ui.chip></a></li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="mt-5 flex flex-wrap gap-2">
                            <x-ui.button type="button" variant="outline" size="sm" data-share data-share-title="{{ $entry->public_name }} bij De Gouden Bol {{ $year }}" data-share-text="{{ $entry->public_name }} is officieel getest door De Gouden Bol {{ $year }} met een {{ number_format($total, 1, ',', '.') }}. {{ config('marketing.hashtag') }}" data-share-url="{{ route('bakkers.toon', $company) }}">Deel dit profiel</x-ui.button>
                        </div>
                    @else
                        <p class="body-text text-[0.9rem]">
                            @if ($edition?->first_test_day)
                                De blinde beoordeling vindt plaats tussen {{ DutchTime::date($edition->first_test_day) }} en {{ DutchTime::date($edition->last_test_day) }}. Vanaf een cijfer van {{ number_format($edition->settings->publishThreshold, 1, ',', '.') }} verschijnt het resultaat hier en op de Voorlijst van {{ $entry->province->name }}.
                            @else
                                Het resultaat verschijnt hier na de blinde beoordeling.
                            @endif
                        </p>
                    @endif
                </x-ui.card>
            </div>

            <aside class="flex flex-col gap-5 lg:sticky lg:top-[100px] lg:self-start">
                @if ($location)
                    <x-ui.card class="!p-7">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-kop text-[1.1rem] font-semibold">Openingstijden</h3>
                            @if ($openNow === true)
                                <x-ui.chip status="succes" :dot="true">Nu open</x-ui.chip>
                            @elseif ($openNow === false)
                                <x-ui.chip status="neutraal" :dot="true">Gesloten</x-ui.chip>
                            @endif
                        </div>
                        @if ($location->openingHours->isEmpty())
                            <p class="text-[0.85rem] text-gedempt">Openingstijden volgen.</p>
                        @else
                            <ul class="text-[0.85rem]">
                                @foreach (OpeningHour::WEEKDAYS as $weekday => $label)
                                    @php $hour = $location->openingHours->firstWhere('weekday', $weekday); @endphp
                                    <li class="flex justify-between border-b border-rand-sterk py-2 last:border-0 {{ $weekday === $today ? '-mx-2 rounded-rij bg-goud-licht px-2' : '' }}">
                                        <span class="font-semibold">{{ $label }}</span>
                                        @if ($hour && ! $hour->is_closed)
                                            <span class="font-bold text-goud-tekst">{{ substr($hour->opens_at, 0, 5) }} &ndash; {{ substr($hour->closes_at, 0, 5) }}</span>
                                        @else
                                            <span class="text-gedempt">Gesloten</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($location->season_from || $location->season_to)
                            <div class="mt-4 rounded-veld bg-zand p-3 text-[0.78rem] leading-relaxed text-gedempt">
                                Seizoen: <strong>{{ $location->season_from ? DutchTime::date($location->season_from, 'D MMMM') : '…' }} &ndash; {{ $location->season_to ? DutchTime::date($location->season_to, 'D MMMM') : '…' }}</strong>
                            </div>
                        @endif
                    </x-ui.card>

                    <x-ui.card class="!p-7">
                        <h3 class="mb-4 border-b border-rand pb-3 font-kop text-[1.1rem] font-semibold">Locatie</h3>
                        <div class="flex items-start gap-3 text-[0.9rem]">
                            <span class="icoonvak !h-8 !w-8 text-[0.8rem]" aria-hidden="true">&#9679;</span>
                            <div>
                                <div class="label !mb-0.5">Adres</div>
                                <div class="font-semibold">{{ $location->street }} {{ $location->house_number }}<br>{{ $location->postcode }} {{ $location->city }}</div>
                            </div>
                        </div>
                        <div class="mt-4 h-[200px] overflow-hidden rounded-kaart-in-kaart bg-zand">
                            @if ($location->lat && $location->lng)
                                <iframe
                                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $location->lng - 0.006 }},{{ $location->lat - 0.003 }},{{ $location->lng + 0.006 }},{{ $location->lat + 0.003 }}&amp;layer=mapnik&amp;marker={{ $location->lat }},{{ $location->lng }}"
                                    title="Kaart met de locatie van {{ $entry->public_name }}"
                                    class="h-full w-full border-0"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                ></iframe>
                            @else
                                <x-ui.placeholder-image label="Kaart volgt" class="h-full !rounded-none" />
                            @endif
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($location->fullAddress()) }}" target="_blank" rel="noopener" class="btn-pill btn--sm mt-4 w-full">Route plannen</a>
                    </x-ui.card>
                @endif

                <x-ui.card class="!p-7">
                    <h3 class="mb-4 border-b border-rand pb-3 font-kop text-[1.1rem] font-semibold">Over de bakkerij</h3>
                    <dl class="flex flex-col gap-3 text-[0.9rem]">
                        <div><dt class="label !mb-0.5">Type</dt><dd class="font-semibold">{{ $company->type->getLabel() }}</dd></div>
                        @if ($company->founded_year)
                            <div><dt class="label !mb-0.5">Opgericht</dt><dd class="font-semibold">{{ $company->founded_year }}</dd></div>
                        @endif
                        @if ($company->website)
                            <div><dt class="label !mb-0.5">Website</dt><dd><a href="{{ $company->website }}" rel="noopener nofollow" target="_blank" class="text-goud-tekst underline">{{ preg_replace('#^https?://#', '', $company->website) }}</a></dd></div>
                        @endif
                        @if (! empty($socials['instagram']))
                            <div><dt class="label !mb-0.5">Instagram</dt><dd><a href="https://instagram.com/{{ $socials['instagram'] }}" rel="noopener nofollow" target="_blank" class="text-goud-tekst underline">@{{ $socials['instagram'] }}</a></dd></div>
                        @endif
                    </dl>
                </x-ui.card>

                @if ($bakesWith->isNotEmpty())
                    <x-ui.card class="!p-7">
                        <h3 class="mb-4 border-b border-rand pb-3 font-kop text-[1.1rem] font-semibold">Bakt met</h3>
                        <div class="flex flex-col gap-3">
                            @foreach ($bakesWith as $sponsor)
                                <x-sponsor-tile :sponsor="$sponsor" :compact="true" label="Sponsor · bevestigd door de bakker" />
                            @endforeach
                        </div>
                        <p class="mt-3 text-[0.72rem] text-gedempt">Sponsoring heeft geen invloed op de keuring of de uitslag.</p>
                    </x-ui.card>
                @endif

                <x-ui.button variant="outline" :href="route('bakkers.index')" class="justify-center">&larr; Alle bakkers</x-ui.button>
            </aside>
        </div>
    </section>
</x-layouts.public>
