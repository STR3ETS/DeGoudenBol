@php
    // Prototype met demodata (geen echte cijfers). Gegevens komen later uit DashboardData en WorkQueue.
    $menu = [
        [['key' => 'overzicht', 'label' => 'Overzicht', 'icoon' => 'huis']],
        'Bakkers' => [
            ['key' => 'bedrijven', 'label' => 'Bedrijven', 'icoon' => 'winkel'],
            ['key' => 'inschrijvingen', 'label' => 'Inschrijvingen', 'icoon' => 'klembord', 'teller' => 3],
        ],
        'Testdagen' => [
            ['key' => 'sessies', 'label' => 'Testsessies', 'icoon' => 'kalender'],
            ['key' => 'scores', 'label' => 'Scorecontrole', 'icoon' => 'ster'],
        ],
        'Uitslag' => [['key' => 'publicaties', 'label' => 'Publicaties', 'icoon' => 'document']],
        'Geld' => [
            ['key' => 'orders', 'label' => 'Orders', 'icoon' => 'euro'],
            ['key' => 'doelen', 'label' => 'Goede doelen', 'icoon' => 'hart'],
        ],
        'Campagne' => [['key' => 'pers', 'label' => 'Pers en sponsors', 'icoon' => 'bliksem']],
    ];
    $werk = [
        ['titel' => 'Voorwaarden publiceren', 'sub' => 'De aanmeldpagina blijft dicht totdat de deelnamevoorwaarden en de privacyverklaring gepubliceerd zijn.', 'icoon' => 'document', 'toon' => 'fout', 'knop' => 'Publiceer nu', 'primair' => true],
        ['titel' => '7 bakkers wachten op betaling', 'sub' => 'Hun plek is gereserveerd. Je hoeft niets te doen, tenzij iemand belt: dan kun je hier de betaling afboeken.', 'icoon' => 'euro', 'toon' => 'waarschuwing', 'knop' => 'Bekijk'],
        ['titel' => '2 profielen keuren', 'sub' => 'Bakkers hebben een foto of tekst aangepast. Jij kijkt even mee voordat het op de site komt.', 'icoon' => 'oog', 'toon' => 'info', 'knop' => 'Keur'],
        ['titel' => 'Testdag 1 november voorbereiden', 'sub' => 'Panelleden uitnodigen en de aanleverslots controleren. Dit kan tot 28 oktober.', 'icoon' => 'kalender', 'toon' => 'goud', 'knop' => 'Open planning'],
    ];
    $stappen = [
        ['Inschrijving', 'Geopend op 12 oktober · 312 bakkers aangemeld', 'klaar'],
        ['Inplannen', 'Bakkers kiezen een aanlevermoment. 280 van 312 hebben dat al gedaan.', 'nu'],
        ['Testdagen', '1 t/m 29 november · het panel proeft, jij ziet alleen testnummers', 'later'],
        ['Scorecontrole en bevriezing', 'Gemiddelden controleren, afwijkers bekijken, 18 december om 12:00 bevriezen', 'later'],
        ['Publicatie', 'Maandag 21 december om 12:00 · twee goedkeurders', 'later'],
    ];
    $mijlpalen = [
        ['wo', '28', 'okt', 'Proefrun testdag', 'intern, om 10:00', 'over 6 dagen', true],
        ['zo', '1', 'nov', 'Eerste testdag', 'hele dag', 'over 10 dagen', false],
        ['vr', '18', 'dec', 'Bevriezing', 'om 12:00', 'over 57 dagen', false],
        ['ma', '21', 'dec', 'Hoofdpublicatie', 'om 12:00', 'over 60 dagen', false],
    ];
    $recent = [
        ['BZ', '[DEMO] Bakkerij Zonnebloem', 'Gelderland · 2 uur geleden', 'succes', 'Betaald'],
        ['DK', '[DEMO] Oliebollenkraam De Ketel', 'Noord-Holland · 5 uur geleden', 'waarschuwing', 'Wacht op betaling'],
        ['FH', '[DEMO] Frituur Het Hoekje', 'Limburg · gisteren', 'succes', 'Betaald'],
    ];
    $provincies = [['Gelderland', 38], ['Noord-Holland', 47], ['Utrecht', 50], ['Limburg', 12], ['Zeeland', 9]];
@endphp
<x-layouts.redesign title="Overzicht" tagline="Backoffice" :menu="$menu" actief="overzicht">
    <x-slot:voet>
        <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="vraag" /></span>Hulp en uitleg</a>
        <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="instellingen" /></span>Instellingen</a>
    </x-slot:voet>

    <header class="ui-app__kop">
        <x-ui.bol class="ui-bol--klein" stemming="blij" />
        <div class="ui-app__kop-tekst">
            <h1 class="ui-app__titel">Goedemorgen, Bennie</h1>
            <p class="ui-app__sub">Donderdag 22 oktober 2026 · De Gouden Bol 2026 · inschrijving open</p>
        </div>
        <div class="ui-app__knoppen">
            <button class="ui-pilknop" type="button" aria-label="3 meldingen"><x-ui.icoon naam="bel" /><span class="ui-badge">3</span></button>
            <button class="ui-pilknop" type="button"><span class="ui-avatar ui-avatar--klein">BV</span><span class="ui-pilknop__tekst">Bennie</span><x-ui.icoon naam="chevron-omlaag" dikte="2.2" /></button>
        </div>
    </header>

    <section class="ui-vandaag ui-app__blok" aria-labelledby="vandaag-titel">
        <div>
            <div class="ui-vandaag__eyebrow"><x-ui.icoon naam="zon" /> Het belangrijkste van vandaag</div>
            <h2 id="vandaag-titel" class="ui-vandaag__titel"><em>Publiceer de voorwaarden</em>, dan gaat de aanmeldpagina open.</h2>
            <p class="ui-vandaag__tekst">De inschrijving is open sinds 12 oktober, maar de deelnamevoorwaarden en de privacyverklaring staan nog op concept. Zodra je ze publiceert, gaat de aanmeldknop op de site vanzelf aan. Dit kost twee minuten.</p>
            <div class="ui-vandaag__acties">
                <a href="#" class="ui-knop ui-knop--groot">Publiceer de voorwaarden <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
                <a href="#" class="ui-knop ui-knop--groot ui-knop--ghost">Eerst bekijken</a>
            </div>
            <details class="ui-waarom mt-4">
                <summary>Waarom is dit nodig? <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary>
                <p>Een bakker gaat bij het aanmelden akkoord met een vaste versie van de voorwaarden. Zonder gepubliceerde versie kan er juridisch niets getekend worden, dus houdt het systeem de aanmeldpagina dicht.</p>
            </details>
        </div>
        <div class="ui-vandaag__zij">
            <div class="ui-ring ui-ring--donker ui-ring--groot" style="--ring-pct: 32"><span class="ui-ring__tekst"><span class="ui-ring__cijfer">32%</span><span class="ui-ring__label">van het seizoen</span></span></div>
            <span class="text-[0.78rem] text-room-zacht">Stap 2 van 5 · Inplannen</span>
        </div>
    </section>

    <div class="ui-sectie">
        <h2 class="ui-sectie__titel">Jouw werk vandaag <span class="ui-sectie__sub">4 dingen. Begin bovenaan, de rest kan wachten.</span></h2>
        <a href="#" class="ui-sectie__link">Alles wat open staat <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
    </div>
    <div class="ui-app__blok grid gap-4 md:grid-cols-2">
        @foreach ($werk as $item)
            <a href="#" class="ui-actie {{ ($item['primair'] ?? false) ? 'ui-actie--primair' : '' }}">
                <span class="ui-icoonvak ui-icoonvak--{{ $item['toon'] }}"><x-ui.icoon :naam="$item['icoon']" /></span>
                <span class="ui-actie__tekst"><span class="ui-actie__titel">{{ $item['titel'] }}</span><span class="ui-actie__sub">{{ $item['sub'] }}</span></span>
                <span class="ui-actie__pijl" aria-hidden="true"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>
            </a>
        @endforeach
    </div>

    <div class="ui-sectie">
        <h2 class="ui-sectie__titel">Hoe staat het ervoor <span class="ui-sectie__sub">Vier cijfers die je vandaag nodig hebt.</span></h2>
    </div>
    <div class="ui-app__blok grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="#" class="ui-tegel ui-tegel--goud"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="klembord" /></span>Bakkers doen mee</div><div class="ui-tegel__cijfer">312</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk de lijst <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>van 600 plekken · 4 nieuw vandaag</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="klembord" dikte="1.2" /></span></a>
        <a href="#" class="ui-tegel ui-tegel--waarschuwing"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--waarschuwing"><x-ui.icoon naam="klok" /></span>Wachten op betaling</div><div class="ui-tegel__cijfer">7</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk wie <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>plek nog 2 dagen gereserveerd</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="klok" dikte="1.2" /></span></a>
        <a href="#" class="ui-tegel ui-tegel--succes"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--succes"><x-ui.icoon naam="euro" /></span>Ontvangen deelnamegeld</div><div class="ui-tegel__cijfer">€ 232.440</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Naar de orders <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>excl. btw · € 5.200 staat nog open</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="euro" dikte="1.2" /></span></a>
        <a href="#" class="ui-tegel ui-tegel--info"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--info"><x-ui.icoon naam="kalender" /></span>Aanlevermoment gekozen</div><div class="ui-tegel__cijfer">280</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Wie nog niet <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>32 bakkers krijgen vrijdag een herinnering</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="kalender" dikte="1.2" /></span></a>
    </div>

    <div class="ui-app__blok grid gap-6 xl:grid-cols-[3fr_2fr]">
        <div class="ui-kaart">
            <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="vlag" /></span><div><div class="ui-kaart__titel">Waar we zijn in het seizoen</div><div class="ui-kaart__sub">Vijf stappen van aanmelden tot publicatie. Elke stap heeft een eigen scherm.</div></div></div>
            <div class="ui-kaart__inhoud !pt-2">
                <ol class="ui-stappenhulp">
                    @foreach ($stappen as $i => [$titel, $tekst, $staat])
                        <li class="ui-stappenhulp__stap ui-stappenhulp__stap--{{ $staat }}">
                            <span class="ui-stappenhulp__nr">@if ($staat === 'klaar')<x-ui.icoon naam="vink" dikte="3" />@else{{ $i + 1 }}@endif</span>
                            <div><span class="ui-stappenhulp__titel">{{ $titel }}@if ($staat === 'nu') <span class="ui-chip ui-chip--goud ui-chip--klein ml-2 align-middle">nu</span>@endif</span><p class="ui-stappenhulp__tekst">{{ $tekst }}</p></div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
        <div class="flex flex-col gap-5">
            <div class="ui-kaart">
                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--espresso"><x-ui.icoon naam="kalender" /></span><div><div class="ui-kaart__titel">Belangrijke data</div><div class="ui-kaart__sub">Wat eraan komt</div></div><div class="ui-kaart__acties"><a href="#" class="ui-sectie__link">Aanpassen</a></div></div>
                <div class="ui-kaart__inhoud !pt-3"><div class="ui-lijst">
                    @foreach ($mijlpalen as [$dag, $nr, $maand, $titel, $sub, $over, $goud])
                        <div class="ui-rij {{ $goud ? 'ui-rij--goud' : '' }}"><span class="ui-datumblok {{ $goud ? 'ui-datumblok--goud' : '' }}"><span class="ui-datumblok__dag">{{ $dag }}</span><span class="ui-datumblok__nr">{{ $nr }}</span><span class="ui-datumblok__maand">{{ $maand }}</span></span><span class="ui-rij__tekst"><span class="ui-rij__titel">{{ $titel }}</span><span class="ui-rij__sub">{{ $sub }}</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--klein {{ $goud ? 'ui-chip--goud' : '' }}">{{ $over }}</span></span></div>
                    @endforeach
                </div></div>
            </div>
            <div class="ui-hulpkaart">
                <div class="ui-hulpkaart__kop"><x-ui.icoon naam="vraag" class="h-5 w-5 text-goud-tekst" /> Hulp nodig?</div>
                <p class="ui-hulpkaart__tekst">Elk scherm heeft rechtsboven een knop <strong>Uitleg</strong> die in gewone taal vertelt wat je hier doet. Kom je er niet uit, dan belt of mailt Eazyonline je terug op dezelfde werkdag.</p>
                <a href="#" class="ui-hulpkaart__link">Open de handleiding <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
            </div>
        </div>
    </div>

    <section class="ui-feest ui-app__blok">
        <x-ui.bol stemming="trots" />
        <div class="ui-feest__tekstvak">
            <h2 class="ui-feest__titel">De <em>300e bakker</em> heeft zich aangemeld</h2>
            <p class="ui-feest__tekst">Halverwege de 600 plekken, zestien dagen eerder dan vorig jaar. Mooi moment om de pers een update te sturen.</p>
            <div class="ui-feest__acties"><a href="#" class="ui-knop ui-knop--klein">Maak een persbericht</a><a href="#" class="ui-knop ui-knop--klein ui-knop--secundair">Later</a></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[3fr_2fr]">
        <div class="ui-kaart">
            <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--info"><x-ui.icoon naam="klembord" /></span><div><div class="ui-kaart__titel">Nieuwe aanmeldingen</div><div class="ui-kaart__sub">12 in de afgelopen twee weken</div></div><div class="ui-kaart__acties"><a href="#" class="ui-sectie__link">Alle bakkers</a></div></div>
            <div class="ui-kaart__inhoud !pt-3">
                <div class="ui-staven mb-4" aria-hidden="true">@foreach ([0, 10, 0, 0, 30, 0, 0, 0, 60, 0, 20, 0, 100, 40] as $i => $h)<span class="ui-staven__staaf {{ $i === 13 ? 'ui-staven__staaf--vandaag' : '' }}"><span class="ui-staven__vulling" style="height: {{ $h }}%"></span></span>@endforeach</div>
                <div class="ui-lijst">
                    @foreach ($recent as [$initialen, $naam, $sub, $toon, $status])
                        <a href="#" class="ui-rij ui-rij--klik"><span class="ui-avatar {{ $loop->odd ? '' : 'ui-avatar--espresso' }}">{{ $initialen }}</span><span class="ui-rij__tekst"><span class="ui-rij__titel">{{ $naam }}</span><span class="ui-rij__sub">{{ $sub }}</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--{{ $toon }} ui-chip--klein">{{ $status }}</span><span class="ui-rij__chevron"><x-ui.icoon naam="chevron-rechts" dikte="2.4" /></span></span></a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="ui-kaart">
            <div class="ui-kaart__kop"><span class="ui-icoonvak"><x-ui.icoon naam="kaart" /></span><div><div class="ui-kaart__titel">Plekken per provincie</div><div class="ui-kaart__sub">Utrecht zit vol. Rood betekent: de wachtlijst staat aan.</div></div></div>
            <div class="ui-kaart__inhoud !pt-3 flex flex-col gap-3">
                @foreach ($provincies as [$p, $n])
                    <div><div class="mb-1 flex justify-between text-[0.78rem] font-bold"><span>{{ $p }}</span><span class="text-gedempt">{{ $n }} van 50</span></div><div class="ui-balk"><div class="ui-balk__vulling {{ $n >= 50 ? '!bg-status-fout' : ($n >= 45 ? '!bg-status-waarschuwing' : '') }}" style="width: {{ $n * 2 }}%"></div></div></div>
                @endforeach
                <div class="ui-tipje"><x-ui.icoon naam="info" /> Vol is vol: de 51e bakker in Utrecht komt automatisch op de wachtlijst en krijgt bericht zodra er een plek vrijkomt.</div>
            </div>
        </div>
    </div>

    <x-slot:mobiel>
        <nav class="ui-mobielnav" aria-label="Menu">
            <a href="#" class="is-actief"><x-ui.icoon naam="huis" />Overzicht</a>
            <a href="#"><x-ui.icoon naam="klembord" />Bakkers</a>
            <a href="#"><x-ui.icoon naam="kalender" />Testdagen</a>
            <a href="#"><x-ui.icoon naam="euro" />Geld</a>
            <a href="#"><x-ui.icoon naam="menu" />Meer</a>
        </nav>
    </x-slot:mobiel>
</x-layouts.redesign>
