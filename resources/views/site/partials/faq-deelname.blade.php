@php
    use App\Domain\Commerce\Models\Package;
    use App\Domain\Edition\Models\Edition;
    use App\Support\DutchTime;
    use App\Support\Money;

    $edition = Edition::current();
    $settings = $edition?->settings;
    $package = $edition ? Package::query()->where('edition_id', $edition->getKey())->active()->orderBy('sort')->first() : null;
    $prijs = $package ? Money::format($package->price_cents) : null;
    // Eén alles-in-één pakket (besluit Bennie 9 nov 2026): dan gelden de losse prijzen en acties niet.
    $pakket = $settings?->bundleEnabled() ? Money::format($settings->bundleCents) : null;
    $pakketNormaal = $pakket && $settings->bundleRegularCents > $settings->bundleCents ? Money::format($settings->bundleRegularCents) : null;
    $opening = $package && ! $pakket && $settings?->openingOfferEnabled() ? Money::format($settings->openingOfferCents) : null;
    $naturel = ! $pakket && $settings?->plainAddonEnabled() ? Money::format($settings->plainAddonCents) : null;
    $actie = $package && ! $pakket && ! $opening && $settings?->discountEnabled() ? Money::format($settings->discountedCents($package->price_cents)) : null;
    $filmpje = $settings?->videoAddonEnabled() ? Money::format($settings->videoAddonCents) : null;
    $filmpjeNormaal = $settings?->videoAddonRegularPrice() ? Money::format($settings->videoAddonRegularPrice()) : null;
    $plekkenVanaf = $settings?->visibilitySpotsStartAtPublication && $edition?->main_publication_at?->isFuture() ? DutchTime::date($edition->main_publication_at) : null;
    $publicatie = $edition?->main_publication_at ? DutchTime::format($edition->main_publication_at, 'dddd D MMMM YYYY [om] HH:mm') : null;

    // Briefing 29 okt §6: deze antwoorden komen uit de instellingen, zodat FAQ, portaal, mails en aanleverbewijs altijd gelijk lopen.
    $vragen = array_values(array_filter([
        $pakket ? ['Wat kost deelname?', "Deelname kost {$pakket} excl. btw".($pakketNormaal ? " in plaats van {$pakketNormaal} (introductieactie)" : '').'. Daar zit alles in: de test van je oliebol met krenten, de test van je naturel oliebol en het ophalen op vrijdag. Je betaalt bij de definitieve inschrijving via iDEAL of kaart en krijgt direct een bevestiging en factuur.'] : null,
        $opening ? ['Wat kost deelname?', "Tijdelijk geldt de openingsactie: je doet mee voor {$opening} in plaats van {$prijs} excl. btw.".($naturel ? " Voor {$naturel} excl. btw extra laat je ook je naturel oliebol testen." : '').' Je betaalt bij de definitieve inschrijving via iDEAL of kaart en krijgt direct een bevestiging en factuur.'] : null,
        $prijs && ! $opening && ! $pakket ? ['Wat kost deelname?', "De standaardprijs is {$prijs} excl. btw.".($actie ? " Met de kortingsactie betaal je {$actie} excl. btw." : '').' Je betaalt bij de definitieve inschrijving via iDEAL of kaart en krijgt direct een bevestiging en factuur.'] : null,
        $actie ? ['Hoe werkt de kortingsactie?', "Maak een filmpje over je deelname, deel het op social media en tag De Gouden Bol. Zet de link naar je post in het deelnemersportaal. Na goedkeuring door de organisatie betaal je {$actie} excl. btw in plaats van {$prijs}. Je plek blijft {$settings->discountHoldDays} dagen gereserveerd terwijl wij kijken."] : null,
        ['Wat neem ik mee?', ($settings->piecesPerEntry ?? 20).' oliebollen, aangeleverd in een doos of zak zonder naam of logo. Je unieke deelnemercode staat op je aanleverbewijs; daarmee is de beoordeling blind.'],
        ['Kan ik mijn aanlevermoment wijzigen?', 'Ja, in het deelnemersportaal tot '.($settings->changeDeadlineHours ?? 24).' uur vooraf. Dat geldt ook voor de vrijdag van de ophaalservice.'],
        $pakket ? ['Hoe komen mijn oliebollen bij jullie?', 'Wij halen ze op, uitsluitend op vrijdag; dat zit in de deelnameprijs. Bij de aanmelding kies je de vrijdag en vul je in waar wij ze ophalen. Een teamlid van De Gouden Bol komt langs.'.($filmpje ? '' : ' Bij het ophalen kunnen wij een kort filmpje maken voor de website en de sociale media van De Gouden Bol.')] : null,
        $filmpje ? ['Kan ik een filmpje laten maken?', "Ja. Vink bij de aanmelding het filmpje aan: wij maken dan bij het ophalen een kort filmpje van je kraam of bakkerij en je oliebollen. Je krijgt het filmpje zelf en wij gebruiken het voor de sociale media van De Gouden Bol. Dat kost {$filmpje} excl. btw".($filmpjeNormaal ? " in plaats van {$filmpjeNormaal}" : '').' en komt op dezelfde rekening als je deelname.'] : null,
        $settings?->pickupEnabled && ! $pakket ? ['Kunnen jullie mijn oliebollen ophalen?', 'Ja, uitsluitend op vrijdag. De voorrijkosten zijn '.Money::format($settings->pickupFeeCents).' excl. btw. Kies het bij de aanmelding (de voorrijkosten komen dan op dezelfde rekening) of later in het portaal. In het portaal kies je de vrijdag en vul je adres en contactpersoon in; pas na betaling staat het vast. Een teamlid van De Gouden Bol haalt de oliebollen op.'] : null,
        $settings?->visibilitySpotsEnabled() ? ['Wat is extra zichtbaarheid?', 'Na je aanmelding kun je in het portaal een plek kopen om uitgelicht te staan op de pagina van je provincie en bovenaan het deelnemersoverzicht, '.($plekkenVanaf ? "vanaf de bekendmaking op {$plekkenVanaf} " : '').'tot en met 31 december. Dit is een betaalde vermelding en staat helemaal los van de test: je score en je plaats in de uitslag bepaalt alleen het onafhankelijke testpanel.'] : null,
        ['Wanneer krijg ik mijn persoonlijke uitslag?', 'Binnen '.($settings->reportDueDays ?? 3).' dagen na de keuring staat je persoonlijke rapport in het portaal: je deelnemercode, de score per onderdeel, je eindscore, de toelichting van het panel en verbeterpunten. Je kunt het als pdf bewaren.'],
        ['Wanneer wordt de landelijke ranking bekend?', ($publicatie ? 'Op '.$publicatie.'.' : 'Op de officiële publicatiedatum.').' Tot die tijd blijft de ranglijst geheim. Iedere provincie krijgt een Top 10; de provinciale nummer één gaat door naar de landelijke finale.'],
        ['Ziet het panel mijn naam?', 'Nee. Het panel ziet alleen de oliebol en een code, nooit je naam, logo of sponsors.'],
        ['Mag ik mijn eigen uitslag gebruiken?', 'Ja. Je mag je eigen uitslag commercieel gebruiken, zolang je geen misleidende claims maakt.'],
        ['Hoe word ik sponsor?', 'Kies een pakket op de sponsorpagina en reken vaste pakketten direct af. Maatwerk en de Top 10 partner vraag je aan. Sponsoring heeft nooit invloed op de beoordeling.'],
    ]));
@endphp
<section class="sec" id="praktisch" aria-labelledby="praktisch-h"><div class="sec-inner"><div class="faq-wrap" style="max-width:860px">
    <section class="faq-cat" aria-labelledby="praktisch-h"><h2 class="h2" id="praktisch-h">Meedoen in {{ $edition?->year }}: praktisch</h2><div class="faq">
        @foreach ($vragen as $i => [$vraag, $antwoord])
            <div class="faq-item"><h3><button class="faq-q" type="button" aria-expanded="false" aria-controls="praktisch-{{ $i }}"><span>{{ $vraag }}</span><span class="faq-icon" aria-hidden="true">+</span></button></h3><div class="faq-a" id="praktisch-{{ $i }}" role="region"><p>{{ $antwoord }}@if ($vraag === 'Hoe word ik sponsor?') <a href="{{ route('sponsoren') }}#pakketten" style="color:var(--gold)">Naar de sponsorpakketten</a>.@endif</p></div></div>
        @endforeach
    </div></section>
</div></div></section>
