@php
    // Prototype van het deelnemersportaal met demodata. Later gevuld vanuit PortalDashboardController.
    $menu = [
        [
            ['key' => 'overzicht', 'label' => 'Mijn deelname', 'icoon' => 'huis'],
            ['key' => 'planning', 'label' => 'Aanleveren', 'icoon' => 'kalender'],
            ['key' => 'uitslag', 'label' => 'Uitslag', 'icoon' => 'ster'],
            ['key' => 'profiel', 'label' => 'Mijn bedrijf', 'icoon' => 'winkel'],
            ['key' => 'facturen', 'label' => 'Facturen', 'icoon' => 'document'],
            ['key' => 'bonnen', 'label' => 'Cadeaubonnen', 'icoon' => 'cadeau'],
        ],
    ];
    $stappen = [
        ['Aangemeld en betaald', 'Op 12 oktober. Factuur 2026-00123 staat onder Facturen.', 'klaar'],
        ['Kies wanneer je je oliebollen brengt', 'Je kiest een dag en tijd tussen 1 en 29 november. Daarna krijg je een aanleverbewijs met je code.', 'nu'],
        ['Het panel proeft', 'Op de testdag. Het panel ziet alleen een nummer, nooit je naam. Jij hoeft niets te doen.', 'later'],
        ['Uitslag', 'Maandag 21 december om 12:00 zie je hier je cijfer en je plek in Gelderland.', 'later'],
    ];
    $vragen = [
        ['Wat neem ik mee op de testdag?', 'Twaalf oliebollen, gebakken op de dag zelf, in een doos zonder naam of logo. De code op je aanleverbewijs is genoeg: wij plakken hem erop.'],
        ['Kan ik mijn aanlevermoment nog wijzigen?', 'Ja, tot 48 uur van tevoren. Open Aanleveren en kies een ander moment. Je oude plek komt vrij voor een andere bakker.'],
        ['Wanneer hoor ik de uitslag?', 'Maandag 21 december om 12:00, tegelijk met heel Nederland. Je krijgt een e-mail en ziet het hier in het portaal.'],
    ];
@endphp
<x-layouts.redesign title="Mijn deelname" tagline="Deelnemersportaal" :menu="$menu" actief="overzicht">
    <x-slot:voet>
        <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="vraag" /></span>Hulp</a>
        <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="uitloggen" /></span>Uitloggen</a>
    </x-slot:voet>

    <header class="ui-app__kop">
        <x-ui.bol class="ui-bol--klein" stemming="blij" />
        <div class="ui-app__kop-tekst">
            <h1 class="ui-app__titel">Hoi, Bakkerij Zonnebloem</h1>
            <p class="ui-app__sub">Je doet mee in Gelderland · De Gouden Bol 2026</p>
        </div>
        <div class="ui-app__knoppen">
            <button class="ui-pilknop" type="button"><span class="ui-avatar ui-avatar--klein">JZ</span><span class="ui-pilknop__tekst">Jan</span><x-ui.icoon naam="chevron-omlaag" dikte="2.2" /></button>
        </div>
    </header>

    <section class="ui-vandaag ui-app__blok" aria-labelledby="klant-vandaag">
        <div>
            <div class="ui-vandaag__eyebrow"><x-ui.icoon naam="zon" /> Jouw volgende stap</div>
            <h2 id="klant-vandaag" class="ui-vandaag__titel">Kies wanneer je <em>je oliebollen brengt</em>.</h2>
            <p class="ui-vandaag__tekst">Je plek is betaald, dat is geregeld. Kies nu een dag en tijd tussen 1 en 29 november. Dat duurt een minuut, en je kunt het tot 48 uur van tevoren nog veranderen.</p>
            <div class="ui-vandaag__acties">
                <a href="#" class="ui-knop ui-knop--groot">Kies een moment <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
                <span class="text-[0.8rem] text-room-zacht">Nog 25 van de 60 momenten vrij in Gelderland</span>
            </div>
        </div>
        <div class="ui-vandaag__zij">
            <div class="ui-ring ui-ring--donker ui-ring--groot" style="--ring-pct: 25"><span class="ui-ring__tekst"><span class="ui-ring__cijfer">1<span class="text-[1.1rem] text-room-zacht">/4</span></span><span class="ui-ring__label">stappen klaar</span></span></div>
            <span class="text-[0.78rem] text-room-zacht">Je bent goed op weg</span>
        </div>
    </section>

    <div class="ui-statusregel ui-statusregel--succes ui-app__blok">
        <span class="ui-icoonvak ui-icoonvak--succes ui-icoonvak--rond"><x-ui.icoon naam="vink" dikte="2.6" /></span>
        <div><strong>Alles is in orde.</strong> Je betaling van € 395,00 is ontvangen op 12 oktober. Op de website sta je als <strong>Bakkerij Zonnebloem, Arnhem</strong>. Niets veranderd? Dan hoef je nu niets te doen.</div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[3fr_2fr]">
        <div class="flex flex-col gap-5">
            <div class="ui-kaart">
                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="vlag" /></span><div><div class="ui-kaart__titel">Zo werkt je deelname</div><div class="ui-kaart__sub">Vier stappen. Jij doet er twee, wij de rest.</div></div></div>
                <div class="ui-kaart__inhoud !pt-2">
                    <ol class="ui-stappenhulp">
                        @foreach ($stappen as $i => [$titel, $tekst, $staat])
                            <li class="ui-stappenhulp__stap ui-stappenhulp__stap--{{ $staat }}">
                                <span class="ui-stappenhulp__nr">@if ($staat === 'klaar')<x-ui.icoon naam="vink" dikte="3" />@else{{ $i + 1 }}@endif</span>
                                <div><span class="ui-stappenhulp__titel">{{ $titel }}</span><p class="ui-stappenhulp__tekst">{{ $tekst }}</p>@if ($staat === 'nu')<a href="#" class="ui-knop ui-knop--klein mt-3">Kies een moment</a>@endif</div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <div class="ui-sectie !mb-0">
                <h2 class="ui-sectie__titel">Ook handig <span class="ui-sectie__sub">Niet verplicht, wel fijn.</span></h2>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <a href="#" class="ui-actie"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="camera" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Zet een foto van je zaak online</span><span class="ui-actie__sub">Bakkers met foto worden drie keer vaker bekeken op de site.</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span></a>
                <a href="#" class="ui-actie"><span class="ui-icoonvak ui-icoonvak--info"><x-ui.icoon naam="mensen" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Geef een collega toegang</span><span class="ui-actie__sub">Dan kan die ook het aanlevermoment zien en de uitslag bekijken.</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span></a>
                <a href="#" class="ui-actie ui-actie--klaar"><span class="ui-icoonvak ui-icoonvak--succes"><x-ui.icoon naam="document" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Factuur 2026-00123</span><span class="ui-actie__sub">Betaald · € 395,00 · download als pdf</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="download" dikte="2.4" /></span></a>
                <span class="ui-actie ui-actie--later" aria-disabled="true"><span class="ui-icoonvak ui-icoonvak--neutraal"><x-ui.icoon naam="klembord" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Aanleverbewijs</span><span class="ui-actie__sub">Komt beschikbaar zodra je een moment hebt gekozen.</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="vergrendel" dikte="2.4" /></span></span>
            </div>
        </div>

        <div class="flex flex-col gap-5">
            <div class="ui-kaart">
                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--espresso"><x-ui.icoon naam="ster" /></span><div><div class="ui-kaart__titel">De uitslag</div><div class="ui-kaart__sub">Maandag 21 december om 12:00</div></div></div>
                <div class="ui-kaart__inhoud !pt-3">
                    <div class="flex items-center gap-4">
                        <span class="ui-datumblok ui-datumblok--goud"><span class="ui-datumblok__dag">ma</span><span class="ui-datumblok__nr">21</span><span class="ui-datumblok__maand">dec</span></span>
                        <div class="text-[0.88rem] leading-relaxed text-gedempt">Nog <strong class="text-espresso">60 dagen</strong>. Je krijgt een e-mail zodra je cijfer er is. Tot die tijd is alles geheim, ook voor ons.</div>
                    </div>
                </div>
            </div>

            <div class="ui-kaart">
                <div class="ui-kaart__kop"><span class="ui-icoonvak"><x-ui.icoon naam="vraag" /></span><div><div class="ui-kaart__titel">Veelgestelde vragen</div><div class="ui-kaart__sub">Kort en in gewone taal</div></div></div>
                <div class="ui-kaart__inhoud !pt-1">
                    @foreach ($vragen as [$vraag, $antwoord])
                        <details class="ui-vraag"><summary>{{ $vraag }} <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary><p>{{ $antwoord }}</p></details>
                    @endforeach
                </div>
            </div>

            <div class="ui-hulpkaart">
                <div class="ui-hulpkaart__kop"><x-ui.icoon naam="hart" class="h-5 w-5 text-goud-tekst" /> We helpen graag</div>
                <p class="ui-hulpkaart__tekst">Kom je ergens niet uit? Bel ons op werkdagen tussen 9 en 17 uur, of mail. Je krijgt dezelfde dag antwoord.</p>
                <div class="mt-3 flex flex-wrap gap-2"><a href="#" class="ui-knop ui-knop--klein ui-knop--secundair"><x-ui.icoon naam="telefoon" /> Bel ons</a><a href="#" class="ui-knop ui-knop--klein ui-knop--secundair"><x-ui.icoon naam="mail" /> Mail ons</a></div>
            </div>
        </div>
    </div>

    <div class="mt-10 mb-3 ui-eyebrow">Zo ziet het portaal eruit na de publicatie</div>
    <section class="ui-feest">
        <x-ui.bol stemming="trots" class="ui-bol--groot" />
        <div class="ui-feest__tekstvak">
            <h2 class="ui-feest__titel">Gefeliciteerd: een <em>8,4</em> en plek 3 in Gelderland</h2>
            <p class="ui-feest__tekst">Je oliebollen zijn officieel getest door De Gouden Bol. Je badge "Officieel getest 2026" en de provinciale top-10-badge staan klaar voor je raam, je website en je socials.</p>
            <div class="ui-feest__acties"><a href="#" class="ui-knop">Bekijk je uitslag</a><a href="#" class="ui-knop ui-knop--secundair"><x-ui.icoon naam="download" /> Download je badges</a><a href="#" class="ui-knop ui-knop--ghost">Deel op Instagram</a></div>
        </div>
    </section>

    <x-slot:mobiel>
        <nav class="ui-mobielnav" aria-label="Menu">
            <a href="#" class="is-actief"><x-ui.icoon naam="huis" />Deelname</a>
            <a href="#"><x-ui.icoon naam="kalender" />Aanleveren</a>
            <a href="#"><x-ui.icoon naam="ster" />Uitslag</a>
            <a href="#"><x-ui.icoon naam="winkel" />Mijn bedrijf</a>
            <a href="#"><x-ui.icoon naam="vraag" />Hulp</a>
        </nav>
    </x-slot:mobiel>
</x-layouts.redesign>
