<section id="liefde" class="mb-20 scroll-mt-28">
    <h2 class="kop-2 mb-3">Nieuw in versie 2</h2>
    <p class="body-text mb-8 max-w-[720px]">Componenten die het platform warmer en eenvoudiger maken. Ze staan in <code class="text-goud-tekst">resources/css/ui-liefde.css</code> en gebruiken dezelfde tokens als de rest.</p>

    <x-stijlgids.blok titel="Vandaag-kaart" klassen=".ui-vandaag --licht .ui-vandaag__eyebrow __titel __tekst __acties __zij" tekst="Bovenaan elk overzicht: één belangrijkste actie, in gewone taal, met de knop erbij. Rechts optioneel een voortgangsring. Op een licht vlak met de variant --licht.">
        <section class="ui-vandaag">
            <div>
                <div class="ui-vandaag__eyebrow"><x-ui.icoon naam="zon" /> Jouw volgende stap</div>
                <h3 class="ui-vandaag__titel">Kies wanneer je <em>je oliebollen brengt</em>.</h3>
                <p class="ui-vandaag__tekst">Je plek is betaald, dat is geregeld. Kies nu een dag en tijd tussen 1 en 29 november. Dat duurt een minuut.</p>
                <div class="ui-vandaag__acties"><a href="#" class="ui-knop ui-knop--groot">Kies een moment <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a><a href="#" class="ui-knop ui-knop--groot ui-knop--ghost">Eerst bekijken</a></div>
                <details class="ui-waarom mt-4"><summary>Waarom is dit nodig? <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary><p>Het panel proeft op vaste momenten. Zonder gekozen moment weten we niet wanneer jouw oliebollen vers binnenkomen.</p></details>
            </div>
            <div class="ui-vandaag__zij"><div class="ui-ring ui-ring--donker ui-ring--groot" style="--ring-pct: 25"><span class="ui-ring__tekst"><span class="ui-ring__cijfer">1/4</span><span class="ui-ring__label">stappen klaar</span></span></div><span class="text-[0.78rem] text-room-zacht">Je bent goed op weg</span></div>
        </section>
    </x-stijlgids.blok>

    <x-stijlgids.blok titel="Actiekaarten" klassen=".ui-actie --primair --klaar --later .ui-actie__titel __sub __pijl" tekst="Grote klikbare kaart voor werk dat op iemand wacht: titel in gewone taal, één zin uitleg, gouden pijl. Klaar is groen en rustig; later is grijs met de reden waarom het nog niet kan.">
        <div class="grid gap-3 md:grid-cols-2">
            <a href="#" class="ui-actie ui-actie--primair"><span class="ui-icoonvak ui-icoonvak--fout"><x-ui.icoon naam="document" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Voorwaarden publiceren</span><span class="ui-actie__sub">De aanmeldpagina blijft dicht totdat dit gebeurd is.</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span></a>
            <a href="#" class="ui-actie"><span class="ui-icoonvak ui-icoonvak--info"><x-ui.icoon naam="oog" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">2 profielen keuren</span><span class="ui-actie__sub">Bakkers pasten een foto of tekst aan. Jij kijkt even mee.</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span></a>
            <a href="#" class="ui-actie ui-actie--klaar"><span class="ui-icoonvak ui-icoonvak--succes"><x-ui.icoon naam="document" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Factuur 2026-00123</span><span class="ui-actie__sub">Betaald · € 395,00 · download als pdf</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="download" dikte="2.4" /></span></a>
            <span class="ui-actie ui-actie--later" aria-disabled="true"><span class="ui-icoonvak ui-icoonvak--neutraal"><x-ui.icoon naam="klembord" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">Aanleverbewijs</span><span class="ui-actie__sub">Komt beschikbaar zodra je een moment hebt gekozen.</span></span><span class="ui-actie__pijl"><x-ui.icoon naam="vergrendel" dikte="2.4" /></span></span>
        </div>
    </x-stijlgids.blok>

    <x-stijlgids.blok titel="Statusregel in gewone taal" klassen=".ui-statusregel --succes --info --waarschuwing --fout" tekst="Vervangt losse statuschips bovenaan een pagina. Eén zin: wat is er aan de hand en wat moet je doen (of juist niet).">
        <div class="flex flex-col gap-3">
            <div class="ui-statusregel ui-statusregel--succes"><span class="ui-icoonvak ui-icoonvak--succes ui-icoonvak--rond"><x-ui.icoon naam="vink" dikte="2.6" /></span><div><strong>Alles is in orde.</strong> Je betaling is ontvangen op 12 oktober. Je hoeft nu niets te doen.</div></div>
            <div class="ui-statusregel ui-statusregel--waarschuwing"><span class="ui-icoonvak ui-icoonvak--waarschuwing ui-icoonvak--rond"><x-ui.icoon naam="klok" /></span><div><strong>Je plek is nog twee dagen gereserveerd.</strong> Rond de betaling af, anders komt de plek vrij voor een andere bakker. <a href="#" class="font-bold text-goud-tekst underline">Betaal nu</a></div></div>
            <div class="ui-statusregel ui-statusregel--info"><span class="ui-icoonvak ui-icoonvak--info ui-icoonvak--rond"><x-ui.icoon naam="info" /></span><div><strong>Het panel proeft op 8 november.</strong> Jij hoeft niets te doen. De uitslag komt op 21 december.</div></div>
            <div class="ui-statusregel ui-statusregel--fout"><span class="ui-icoonvak ui-icoonvak--fout ui-icoonvak--rond"><x-ui.icoon naam="waarschuwing" /></span><div><strong>De aanmeldpagina staat dicht.</strong> Publiceer de deelnamevoorwaarden, dan gaat hij vanzelf open.</div></div>
        </div>
    </x-stijlgids.blok>

    <x-stijlgids.blok titel="Stappenhulp" klassen=".ui-stappenhulp .ui-stappenhulp__stap --klaar --nu --later __nr __titel __tekst" tekst="Verticaal overzicht van waar iemand staat. De huidige stap is groter en goud; wat klaar is krijgt een vinkje; wat later komt staat gedempt maar wel uitgelegd, zodat niemand verrast wordt.">
        <ol class="ui-stappenhulp max-w-[560px]">
            <li class="ui-stappenhulp__stap ui-stappenhulp__stap--klaar"><span class="ui-stappenhulp__nr"><x-ui.icoon naam="vink" dikte="3" /></span><div><span class="ui-stappenhulp__titel">Aangemeld en betaald</span><p class="ui-stappenhulp__tekst">Op 12 oktober. Factuur staat onder Facturen.</p></div></li>
            <li class="ui-stappenhulp__stap ui-stappenhulp__stap--nu"><span class="ui-stappenhulp__nr">2</span><div><span class="ui-stappenhulp__titel">Kies wanneer je je oliebollen brengt</span><p class="ui-stappenhulp__tekst">Een dag en tijd tussen 1 en 29 november.</p><a href="#" class="ui-knop ui-knop--klein mt-3">Kies een moment</a></div></li>
            <li class="ui-stappenhulp__stap ui-stappenhulp__stap--later"><span class="ui-stappenhulp__nr">3</span><div><span class="ui-stappenhulp__titel">Het panel proeft</span><p class="ui-stappenhulp__tekst">Jij hoeft niets te doen.</p></div></li>
            <li class="ui-stappenhulp__stap ui-stappenhulp__stap--later"><span class="ui-stappenhulp__nr">4</span><div><span class="ui-stappenhulp__titel">Uitslag</span><p class="ui-stappenhulp__tekst">Maandag 21 december om 12:00.</p></div></li>
        </ol>
    </x-stijlgids.blok>

    <x-stijlgids.blok titel="Hulp op de plek zelf" klassen=".ui-hulpkaart .ui-tipje .ui-waarom .ui-vraag" tekst="Vier maten hulp: een hulpkaart aan het eind van elke pagina, een tipje in een kaart, een Waarom-details onder een actie en een vraag-en-antwoord-lijst.">
        <div class="grid gap-5 md:grid-cols-2">
            <div class="flex flex-col gap-4">
                <div class="ui-hulpkaart"><div class="ui-hulpkaart__kop"><x-ui.icoon naam="vraag" class="h-5 w-5 text-goud-tekst" /> Hulp nodig?</div><p class="ui-hulpkaart__tekst">Elk scherm heeft rechtsboven een knop Uitleg. Kom je er niet uit, dan bellen we je terug op dezelfde werkdag.</p><a href="#" class="ui-hulpkaart__link">Open de handleiding <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a></div>
                <div class="ui-tipje"><x-ui.icoon naam="info" /> Vol is vol: de 51e bakker komt automatisch op de wachtlijst en krijgt bericht zodra er een plek vrijkomt.</div>
                <details class="ui-waarom"><summary>Waarom twee goedkeurders? <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary><p>Een publicatie is onomkeerbaar. Daarom kijkt altijd iemand anders mee dan degene die hem klaarzet.</p></details>
            </div>
            <div class="ui-kaart"><div class="ui-kaart__inhoud !pt-1">
                <details class="ui-vraag"><summary>Wat neem ik mee op de testdag? <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary><p>Twaalf oliebollen, gebakken op de dag zelf, in een doos zonder naam of logo.</p></details>
                <details class="ui-vraag"><summary>Kan ik mijn moment nog wijzigen? <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary><p>Ja, tot 48 uur van tevoren.</p></details>
                <details class="ui-vraag"><summary>Wanneer hoor ik de uitslag? <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary><p>Maandag 21 december om 12:00, tegelijk met heel Nederland.</p></details>
            </div></div>
        </div>
    </x-stijlgids.blok>

    <x-stijlgids.blok titel="Voortgangsring, feestkaart, mascotte" klassen=".ui-ring --donker --groot .ui-feest .ui-bol --klein --groot" tekst="De ring toont voortgang in één oogopslag (CSS-variabele --ring-pct). De feestkaart is voor mijlpalen. De mascotte heeft drie stemmingen: blij, trots en neutraal, en is altijd decoratief.">
        <div class="flex flex-wrap items-center gap-6 mb-6">
            <div class="ui-ring" style="--ring-pct: 32"><span class="ui-ring__tekst"><span class="ui-ring__cijfer">32%</span><span class="ui-ring__label">seizoen</span></span></div>
            <div class="ui-ring ui-ring--groot" style="--ring-pct: 75"><span class="ui-ring__tekst"><span class="ui-ring__cijfer">3/4</span><span class="ui-ring__label">stappen</span></span></div>
            <x-ui.bol stemming="blij" />
            <x-ui.bol stemming="trots" />
            <x-ui.bol stemming="neutraal" />
            <x-ui.bol stemming="blij" class="ui-bol--klein" />
        </div>
        <section class="ui-feest">
            <x-ui.bol stemming="trots" />
            <div class="ui-feest__tekstvak"><h3 class="ui-feest__titel">De <em>300e bakker</em> heeft zich aangemeld</h3><p class="ui-feest__tekst">Halverwege de 600 plekken, zestien dagen eerder dan vorig jaar.</p><div class="ui-feest__acties"><a href="#" class="ui-knop ui-knop--klein">Maak een persbericht</a><a href="#" class="ui-knop ui-knop--klein ui-knop--secundair">Later</a></div></div>
        </section>
    </x-stijlgids.blok>

    <x-stijlgids.blok titel="Rustig formulier met plakbalk" klassen=".ui-rustig .ui-plakbalk .ui-plakbalk__tekst" tekst="Eén kolom, grote velden (52 px), labels als zinnen, hulptekst onder elk veld. De plakbalk blijft onderin staan met één primaire knop en vertelt wat er gebeurt als je klikt.">
        <form class="ui-rustig" onsubmit="return false">
            <div class="ui-veld"><label class="ui-label" for="lf-naam">Hoe heet je zaak op de website?</label><input id="lf-naam" class="ui-invoer" placeholder="Bijvoorbeeld: Bakkerij Zonnebloem, Arnhem"><p class="ui-hulp">Dit is wat bezoekers zien. Je KvK-naam hoeft hier niet.</p></div>
            <div class="ui-veld"><label class="ui-label" for="lf-prov">In welke provincie doe je mee?</label><div class="ui-keuze" data-ui-keuze><select id="lf-prov" class="ui-keuze__native"><option value="">Kies een provincie</option><option>Gelderland</option><option>Utrecht</option><option>Zeeland</option></select></div><p class="ui-hulp">Je doet mee in de provincie waar je oliebollen bakt, niet waar je woont.</p></div>
            <div class="ui-veld"><label class="ui-label" for="lf-mail">Op welk e-mailadres mogen we je bereiken?</label><input id="lf-mail" type="email" class="ui-invoer" placeholder="naam@bakkerij.nl"><p class="ui-hulp">Hier komt de bevestiging, het aanleverbewijs en de uitslag.</p></div>
            <div class="ui-plakbalk"><span class="ui-plakbalk__tekst"><strong>Nog niets opgeslagen.</strong> Je kunt alles later nog aanpassen.</span><button class="ui-knop ui-knop--secundair" type="button">Later verder</button><button class="ui-knop" type="submit">Opslaan en verder <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></button></div>
        </form>
    </x-stijlgids.blok>

    <x-stijlgids.blok titel="Klaar-staat en sectietitels" klassen=".ui-klaar .ui-sectie .ui-sectie__titel __sub __link" tekst="Als er niets te doen is, zeg dat met een glimlach. Sectietitels krijgen een ondertitel die uitlegt waarom de sectie er is.">
        <div class="ui-sectie"><h3 class="ui-sectie__titel">Jouw werk vandaag <span class="ui-sectie__sub">Begin bovenaan, de rest kan wachten.</span></h3><a href="#" class="ui-sectie__link">Alles wat open staat <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a></div>
        <div class="ui-klaar"><x-ui.bol stemming="blij" class="ui-bol--klein" /><div class="ui-klaar__titel">Je bent helemaal bij</div><p class="ui-klaar__tekst">Er wacht niets op je. De eerstvolgende datum is de proefrun op woensdag 28 oktober.</p></div>
    </x-stijlgids.blok>
</section>
