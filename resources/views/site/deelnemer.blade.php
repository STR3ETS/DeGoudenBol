@php
    use App\Domain\Participants\Models\OpeningHour;
    use App\Support\DutchTime;

    $year = $edition?->year ?? now()->year;
    $story = $profile?->publishedStory();
    $tagline = $profile?->publishedTagline() ?? $entry->tagline;
    $specialties = $profile?->publishedSpecialties() ?? [];
    $city = $location?->city;
    $address = $location ? trim($location->street.' '.$location->house_number).'<br>'.trim($location->postcode.' '.$location->city) : null;
    $routeQuery = $location ? urlencode(trim($location->street.' '.$location->house_number.' '.$location->city)) : null;
    $badge = $total !== null ? "Officieel getest – De Gouden Bol {$year}" : "Deelnemer De Gouden Bol {$year}";
    $dagen = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];
    $hours = collect($location?->openingHours ?? [])->keyBy('weekday');
    $hoursJson = collect($dagen)->mapWithKeys(function (string $dag, int $i) use ($hours) {
        $hour = $hours->get($i + 1);

        return [$dag => $hour === null || $hour->is_closed ? 'gesloten' : substr((string) $hour->opens_at, 0, 5).'-'.substr((string) $hour->closes_at, 0, 5)];
    });
    $description = $tagline ?: $entry->public_name.' doet mee aan De Gouden Bol '.$year.' in '.$entry->province->name.'.';
    $fotos = ['b393fcd0d20bcd9d3a91868dab4c0a233c571787', '6063ee2b281fb9ba9d271b8bf92578fe65828f46', '6efde2762874a33f13d585207d006039014aa8f1', '83e9a266dc943c3670734663bd5a2d59eff4b963', '2e767c88d917a3dfceded31fdd371e77810b3b69'];
    $foto = '/site/assets/'.$fotos[crc32($company->slug) % count($fotos)].'.jpg';
@endphp
<x-layouts.site :title="$entry->public_name" :description="$description" main-class="page-sec">
    <x-slot:structured>
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Bakery',
            'name' => $entry->public_name,
            'description' => $tagline,
            'url' => route('bakkers.toon', $company),
            'address' => $location ? ['@type' => 'PostalAddress', 'streetAddress' => trim($location->street.' '.$location->house_number), 'postalCode' => $location->postcode, 'addressLocality' => $location->city, 'addressCountry' => 'NL'] : null,
            'geo' => $location?->lat ? ['@type' => 'GeoCoordinates', 'latitude' => $location->lat, 'longitude' => $location->lng] : null,
            'sameAs' => array_values(array_filter([$company->website])),
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Alle deelnemers', 'item' => route('bakkers.index')], ['@type' => 'ListItem', 'position' => 2, 'name' => $entry->public_name, 'item' => route('bakkers.toon', $company)]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    </x-slot:structured>

    <div class="hero-profiel"><img src="{{ $foto }}" sizes="100vw" width="1600" height="900" alt="" decoding="async" fetchpriority="high">
        <div class="hero-profiel-ov"></div>
        <div class="hero-profiel-content"><div class="hero-profiel-inner"><div>
            <nav class="profiel-breadcrumb" aria-label="Kruimelpad"><ol><li><a href="{{ route('bakkers.index') }}">&#8592; Alle deelnemers</a></li><li aria-current="page">{{ $entry->public_name }}</li></ol></nav>
            <h1 class="profiel-naam" id="h1">{{ $entry->public_name }} <em></em></h1>
            <div class="profiel-meta">
                @if ($total !== null)<span class="profiel-score"><span aria-hidden="true">★</span> {{ number_format($total, 1, ',', '.') }}</span>@endif
                <span class="profiel-badge-w">{{ $badge }}</span>
                <span class="profiel-city"><span aria-hidden="true">&#9679;</span> {{ $city ?: $entry->province->name }}@if ($city) &middot; {{ $entry->province->name }}@endif</span>
                @if ($position?->position)<span class="profiel-badge-w">Plaats {{ $position->position }} in {{ $entry->province->name }}</span>@endif
            </div></div>
            <div class="profiel-share"><button class="share-btn" type="button" data-share aria-label="Deel dit profiel">&#10064;</button>@if ($routeQuery)<a class="share-btn" href="https://www.google.com/maps/search/?api=1&amp;query={{ $routeQuery }}" target="_blank" rel="noopener" aria-label="Routebeschrijving (opent in een nieuw tabblad)">&#9655;</a>@endif</div>
        </div></div></div>

    <section class="sec" aria-label="Profiel"><div class="sec-inner"><div class="profiel-layout">
        <div class="profiel-main">
            <div class="block rev"><span class="eyebrow">Het verhaal</span><h2 class="h2">Over {{ $entry->public_name }}</h2>
                @if ($tagline)<p class="body-text" style="margin-bottom:18px"><strong>{{ $tagline }}</strong></p>@endif
                @if ($story)
                    @foreach (preg_split('/\R{2,}/', trim($story)) as $alinea)
                        <p class="body-text" style="margin-bottom:18px">{{ $alinea }}</p>
                    @endforeach
                @else
                    <p class="body-text">{{ $entry->public_name }} doet mee aan De Gouden Bol {{ $year }} in {{ $entry->province->name }}. Het verhaal achter de oliebollen volgt zodra de bakker het in het portaal heeft ingevuld.</p>
                @endif
                @if ($specialties !== [])
                    <p class="body-text" style="margin-top:12px"><strong>Specialiteiten:</strong> {{ implode(', ', $specialties) }}</p>
                @endif
                @if ($bakesWith->isNotEmpty())
                    <p class="body-text" style="margin-top:12px;font-size:.85rem">Bakt met {{ $bakesWith->map(fn ($sponsor) => $sponsor->name)->implode(', ') }}.</p>
                @endif
            </div>
            <section class="score-card rev" aria-labelledby="keur-h"><div class="score-head"><div><span class="eyebrow">De Gouden Bol {{ $year }}</span><h2 id="keur-h">Beoordeling</h2></div>
                @if ($total !== null)
                    <div class="score-total"><b>{{ number_format($total, 1, ',', '.') }}</b><span>{{ (int) round($total * 10) }} van 100 punten</span></div>
                @else
                    <div class="score-total"><b>&ndash;</b><span>nog niet beoordeeld</span></div>
                @endif
            </div>
                @if ($total !== null)
                    <p class="body-text" style="margin-top:18px">Blind geproefd door het panel van De Gouden Bol en gepubliceerd op {{ DutchTime::format($entry->published_at, 'D MMMM YYYY') }}. Het cijfer is het gemiddelde van alle geldige scorekaarten, afgerond op één decimaal.@if ($position?->position) Op dit moment plaats {{ $position->position }} op de Voorlijst van {{ $entry->province->name }}.@endif</p>
                    <p class="body-text" style="margin-top:12px;font-size:.8rem">Deelscores per onderdeel worden na de bevriezing gepubliceerd voor de definitieve Top 10. <a href="{{ route('provincies.show', $entry->province) }}" style="color:var(--gold)">Bekijk de Voorlijst van {{ $entry->province->name }}</a>.</p>
                @else
                    <p class="body-text" style="margin-top:18px">De oliebollen van {{ $entry->public_name }} zijn nog niet geproefd, of de score is nog niet gepubliceerd. Scores verschijnen in vaste publicatiemomenten; alleen scores vanaf 5,0 worden openbaar.</p>
                @endif
            </section>
        </div>
        <aside class="profiel-sidebar" aria-label="Praktische gegevens">
            <section class="info-card rev" aria-labelledby="ot-h"><div class="info-card-head"><h2 class="info-card-title" id="ot-h">Openingstijden</h2>@if ($openNow !== null)<span class="tag-keur">{{ $openNow ? 'Nu open' : 'Nu gesloten' }}</span>@endif</div>
                @if ($hours->isEmpty())
                    <p class="ot-note"><span aria-hidden="true">&#9432;</span> Openingstijden volgen</p>
                @else
                    <ul class="ot-list" data-hours='{{ $hoursJson->toJson() }}'>
                        @foreach ($dagen as $i => $dag)
                            @php $hour = $hours->get($i + 1); @endphp
                            <li class="ot-row" data-day="{{ $dag }}"><span class="ot-dag">{{ OpeningHour::WEEKDAYS[$i + 1] }}</span>@if ($hour === null || $hour->is_closed)<span class="ot-tijd closed">Gesloten</span>@else<span class="ot-tijd open">{{ substr((string) $hour->opens_at, 0, 5) }} &ndash; {{ substr((string) $hour->closes_at, 0, 5) }}</span>@endif</li>
                        @endforeach
                    </ul>
                    @if ($location?->season_from && $location?->season_to)<p class="ot-note"><span aria-hidden="true">&#9432;</span> Seizoen {{ DutchTime::date($location->season_from, 'D MMMM') }} t/m {{ DutchTime::date($location->season_to, 'D MMMM') }}</p>@endif
                @endif
            </section>
            <section class="info-card rev" aria-labelledby="loc-h"><h2 class="info-card-title" id="loc-h">Locatie &amp; contact</h2>
                <div class="info-row"><div class="info-icon" aria-hidden="true">&#9679;</div><div><div class="info-label">Adres of standplaats</div>@if ($address)<address class="info-value">{!! $address !!}</address>@else<div class="info-value"><span style="color:var(--muted);font-style:italic;font-weight:400">Niet opgegeven</span></div>@endif</div></div>
                <div class="info-row"><div class="info-icon" aria-hidden="true">&#9737;</div><div><div class="info-label">Website</div><div class="info-value small">@if ($company->website)<a href="{{ $company->website }}" rel="noopener nofollow" target="_blank">{{ preg_replace('#^https?://(www\.)?#', '', $company->website) }}</a>@else<span style="color:var(--muted);font-style:italic;font-weight:400">Niet opgegeven</span>@endif</div></div></div>
                <div class="info-row"><div class="info-icon" aria-hidden="true">&#9733;</div><div><div class="info-label">Type</div><div class="info-value small">{{ $company->type?->getLabel() }}@if ($company->founded_year) &middot; sinds {{ $company->founded_year }}@endif</div></div></div>
            </section>
            <div class="side-actions rev"><a href="{{ route('bakkers.index') }}" class="btn-pill-out">&#8592; Alle deelnemers</a><a href="{{ route('provincies.show', $entry->province) }}" class="btn-pill"><span>Voorlijst {{ $entry->province->name }}</span></a></div>
        </aside>
    </div></div></section>
</x-layouts.site>
