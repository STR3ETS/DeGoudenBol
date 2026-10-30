@php
    use App\Domain\Commerce\Models\Package;
    use App\Domain\Edition\Models\Edition;
    use App\Support\DutchTime;
    use App\Support\Money;

    $edition = Edition::current();
    $settings = $edition?->settings;
    $package = $edition ? Package::query()->where('edition_id', $edition->getKey())->active()->orderBy('sort')->first() : null;
    $prijs = $package ? Money::format($package->price_cents) : null;
    $actie = $package && $settings?->discountEnabled() ? Money::format($settings->discountedCents($package->price_cents)) : null;
    $publicatie = $edition?->main_publication_at ? DutchTime::format($edition->main_publication_at, 'dddd D MMMM YYYY [om] HH:mm') : null;

    // Briefing 29 okt §6: deze antwoorden komen uit de instellingen, zodat FAQ, portaal, mails en aanleverbewijs altijd gelijk lopen.
    $vragen = array_values(array_filter([
        $prijs ? ['Wat kost deelname?', "De standaardprijs is {$prijs} excl. btw.".($actie ? " Met de kortingsactie betaal je {$actie} excl. btw." : '').' Je betaalt bij de definitieve inschrijving via iDEAL of kaart en krijgt direct een bevestiging en factuur.'] : null,
        $actie ? ['Hoe werkt de kortingsactie?', "Maak een filmpje over je deelname, deel het op social media en tag De Gouden Bol. Zet de link naar je post in het deelnemersportaal. Na goedkeuring door de organisatie betaal je {$actie} excl. btw in plaats van {$prijs}. Je plek blijft {$settings->discountHoldDays} dagen gereserveerd terwijl wij kijken."] : null,
        ['Wat neem ik mee?', ($settings->piecesPerEntry ?? 20).' oliebollen, aangeleverd in een doos of zak zonder naam of logo. Je unieke deelnemercode staat op je aanleverbewijs; daarmee is de beoordeling blind.'],
        ['Kan ik mijn aanlevermoment wijzigen?', 'Ja, in het deelnemersportaal tot '.($settings->changeDeadlineHours ?? 24).' uur vooraf. Dat geldt ook voor de vrijdag van de ophaalservice.'],
        $settings?->pickupEnabled ? ['Kunnen jullie mijn oliebollen ophalen?', 'Ja, uitsluitend op vrijdag. De voorrijkosten zijn '.Money::format($settings->pickupFeeCents).' excl. btw. Je vraagt de ophaalservice aan in het portaal, vult adres en contactpersoon in en betaalt direct; pas na betaling staat het vast. Een teamlid van De Gouden Bol haalt de oliebollen op.'] : null,
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
