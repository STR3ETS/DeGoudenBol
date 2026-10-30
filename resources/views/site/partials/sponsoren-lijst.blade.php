@php
    use App\Domain\Commerce\Models\Product;
    use App\Domain\Commerce\Models\SponsorOffer;
    use App\Domain\Commerce\Services\SponsorPlacements;
    use App\Domain\Edition\Models\Edition;
    use App\Support\Money;

    $edition = Edition::current();
    $overview = $edition ? app(SponsorPlacements::class)->overview($edition) : collect();
    $groepen = [
        ['Landelijke partners', $overview->get('national', collect()), fn ($p) => $p->label ?: 'Landelijk partner'],
        ['Provinciepartners', $overview->get('province', collect())->sortBy(fn ($p) => $p->province?->sort), fn ($p) => $p->locationLabel()],
        ['Finale en landelijke Top 10', $overview->get('national_top', collect())->merge($overview->get('final', collect())), fn ($p) => $p->locationLabel()],
        ['Sponsoren', $overview->get('homepage', collect()), fn ($p) => 'Sponsor'],
    ];
    $heeftSponsoren = collect($groepen)->contains(fn ($g) => $g[1]->isNotEmpty());
    $products = $edition ? Product::query()->where('edition_id', $edition->getKey())->orderBy('sort')->get()->filter(fn (Product $p) => $p->code->onWebsite()) : collect();
    $acties = $edition ? SponsorOffer::query()->where('edition_id', $edition->getKey())->visible()->with('sponsor')->latest('reviewed_at')->get() : collect();
    $charity = $edition?->settings->charityPercentage ?? 10;
@endphp
{{-- Briefing 29 okt §8: de commerciële sponsorpagina met echte pakketten, Stripe-checkout en maatwerkroute. --}}
<section class="sec" id="pakketten" aria-labelledby="pakketten-h"><div class="sec-inner">
    <span class="eyebrow rev">Sponsorpakketten {{ $edition?->year }}</span>
    <h2 class="h2 rev" id="pakketten-h">Verbind uw bedrijf aan de zoektocht naar <em>de beste oliebol van Nederland</em></h2>
    <ul class="list-plain cols-2" style="margin-bottom:28px">
        <li><span><b>Provinciale rankings</b>: iedere provincie krijgt een eigen Top 10; de nummer één gaat door naar de landelijke finale.</span></li>
        <li><span><b>Landelijke Top 10</b>: de finale bepaalt de beste oliebollen van Nederland, met landelijke publiciteit.</span></li>
        <li><span><b>Zichtbaarheid</b>: uw logo op de site, bij deelnemers die uw product gebruiken en rond de finale.</span></li>
        <li><span><b>Maatschappelijke bijdrage</b>: {{ $charity }}% van de opbrengst gaat naar goede doelen; u mag zelf een doel voordragen.</span></li>
    </ul>

    @if ($products->isNotEmpty())
        <div class="stagger-grid" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
            @foreach ($products as $product)
                <div class="jury-slot" style="display:flex;flex-direction:column;text-align:left">
                    <strong style="font-size:1.05rem">{{ $product->name }}</strong>
                    <p class="body-text" style="margin:6px 0 0;font-size:.95rem;font-weight:700">
                        @if ($product->isCustomPriced())
                            Maatwerk, in overleg
                        @else
                            {{ Money::format($product->price_cents) }} <span style="font-weight:400;font-size:.8rem">excl. btw</span>
                        @endif
                    </p>
                    @if ($product->extra_link_price_cents)<p class="body-text" style="margin:2px 0 0;font-size:.8rem">Elke extra vermelding {{ Money::format($product->extra_link_price_cents) }}</p>@endif
                    @if ($product->description)<p class="body-text" style="margin:8px 0 14px;font-size:.85rem;flex:1">{{ $product->description }}</p>@endif
                    <a href="{{ route('sponsoring.aanmelden', ['pakket' => $product->code->value]) }}" class="btn-pill" style="align-self:flex-start"><span>{{ $product->isCustomPriced() || $product->code->value === 'national_top' ? 'Aanvragen' : 'Kies dit pakket' }}</span></a>
                </div>
            @endforeach
        </div>
    @endif

    <h3 style="margin:34px 0 12px">Zo werkt het</h3>
    <ol class="list-plain">
        <li><span><b>Kies een pakket</b> en vul uw gegevens, factuuradres en logo in.</span></li>
        <li><span><b>Vaste pakketten</b> rekent u direct af via iDEAL of kaart; u krijgt de factuur en een bevestiging per mail.</span></li>
        <li><span><b>Wij controleren</b> uw logo en gegevens en zetten uw vermelding online. Een koppeling bij een deelnemer verschijnt pas als die deelnemer akkoord geeft.</span></li>
        <li><span><b>Maatwerk en Top 10 partner</b>: u dient een aanvraag in, zonder prijs of betaalverplichting. Wij nemen contact op.</span></li>
    </ol>
    <div class="btn-row" style="justify-content:flex-start;margin-top:22px">
        <a href="{{ route('sponsoring.aanmelden') }}" class="btn-pill btn-pill-lg"><span>Sponsor worden</span></a>
        <a href="{{ route('sponsoring.beheer') }}" class="btn-light">Al sponsor? Naar uw omgeving</a>
    </div>
</div></section>

@if ($heeftSponsoren)
<section class="sec bg-linen" id="partners" aria-labelledby="partners-h"><div class="sec-inner">
    <span class="eyebrow rev">Editie {{ $edition?->year }}</span><h2 class="h2 rev" id="partners-h">Onze partners en sponsoren</h2>
    @foreach ($groepen as [$titel, $plaatsingen, $label])
        @if ($plaatsingen->isNotEmpty())
            <h3 style="margin:28px 0 12px">{{ $titel }}</h3>
            <div class="stagger-grid" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
                @foreach ($plaatsingen as $placement)
                    @include('site.partials.sponsor-kaart', ['sponsor' => $placement->sponsor, 'label' => $label($placement)])
                @endforeach
            </div>
        @endif
    @endforeach
</div></section>
@endif

@if ($acties->isNotEmpty())
<section class="sec" id="acties" aria-labelledby="acties-h"><div class="sec-inner">
    <span class="eyebrow rev">Sponsoracties</span><h2 class="h2 rev" id="acties-h">Wat sponsoren aanbieden</h2>
    <div class="stagger-grid" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
        @foreach ($acties as $actie)
            <div class="jury-slot" style="text-align:left"><strong>{{ $actie->title }}</strong><p class="body-text" style="margin:6px 0 0;font-size:.85rem">{{ $actie->description }}</p><p class="body-text" style="margin:8px 0 0;font-size:.78rem">{{ $actie->sponsor->name }} · {{ $actie->type->getLabel() }} · voor {{ mb_strtolower($actie->audience->getLabel()) }}</p></div>
        @endforeach
    </div>
</div></section>
@endif

<section class="sec" aria-label="Onafhankelijkheid"><div class="sec-inner">
    <p class="body-text" style="font-size:.85rem">Sponsoring is gelabeld en staat altijd los van de beoordeling: het panel ziet geen sponsorinformatie, sponsoren zien geen testgegevens en hebben geen invloed op score of ranking. Top 10-vermeldingen verschijnen pas nadat de ranking openbaar is.</p>
</div></section>
