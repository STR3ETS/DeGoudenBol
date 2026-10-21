            <section id="structuur" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Tabs, accordeon, kruimels, stappen, tijdlijn</h2>

                <x-stijlgids.blok titel="Tabs" klassen="[data-ui-tabs] .ui-tabs(--lijn) .ui-tabs__tab .ui-tabs__paneel">
                    <div class="grid gap-8 md:grid-cols-2">
                        <div class="ui-tabs" data-ui-tabs>
                            <div class="ui-tabs__lijst"><button class="ui-tabs__tab" aria-selected="true">Inschrijvingen <span class="ui-badge ui-badge--espresso">3</span></button><button class="ui-tabs__tab">Orders</button><button class="ui-tabs__tab">Accounts</button></div>
                            <div class="ui-tabs__paneel text-[0.85rem] text-gedempt">Pil-tabs voor werkvoorraad en dossiers.</div>
                            <div class="ui-tabs__paneel text-[0.85rem] text-gedempt" hidden>Orders van dit bedrijf.</div>
                            <div class="ui-tabs__paneel text-[0.85rem] text-gedempt" hidden>Accounts met rol.</div>
                        </div>
                        <div class="ui-tabs ui-tabs--lijn" data-ui-tabs>
                            <div class="ui-tabs__lijst"><button class="ui-tabs__tab" aria-selected="true">Algemeen</button><button class="ui-tabs__tab">Datums</button><button class="ui-tabs__tab">Instellingen</button></div>
                            <div class="ui-tabs__paneel text-[0.85rem] text-gedempt">Lijn-tabs voor formulieren met secties.</div>
                            <div class="ui-tabs__paneel text-[0.85rem] text-gedempt" hidden>Datums van de editie.</div>
                            <div class="ui-tabs__paneel text-[0.85rem] text-gedempt" hidden>Rekenregels en capaciteit.</div>
                        </div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Accordeon en kruimelpad" klassen=".ui-accordeon (details) .ui-kruimels">
                    <nav class="ui-kruimels mb-5" aria-label="Kruimelpad"><a href="#">Bakkers</a><x-ui.icoon naam="chevron-rechts" dikte="2.5" /><a href="#">Bedrijven</a><x-ui.icoon naam="chevron-rechts" dikte="2.5" /><span aria-current="page">Oliebollenkraam De Ketel</span></nav>
                    <details class="ui-accordeon" open><summary>Hoe wordt een oliebol beoordeeld?<span class="ui-accordeon__plus"><x-ui.icoon naam="plus" dikte="2.4" /></span></summary><div class="ui-accordeon__inhoud">Blind, op acht onderdelen van een 100-puntenmodel, door minimaal zes panelleden.</div></details>
                    <details class="ui-accordeon"><summary>Wanneer verschijnen de resultaten?<span class="ui-accordeon__plus"><x-ui.icoon naam="plus" dikte="2.4" /></span></summary><div class="ui-accordeon__inhoud">Dinsdag en vrijdag om 12:00 uur, na goedkeuring door twee personen.</div></details>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Processtappen en tijdlijn" klassen=".ui-stappen .ui-stap(--klaar --actief) .ui-tijdlijn .ui-tijdlijn__item(--klaar --nu)">
                    <div class="ui-stappen mb-8">
                        @foreach ([['Aanmelding', 312, 'klaar'], ['Inplannen', 280, 'klaar'], ['Ontvangst', 64, 'actief'], ['Registratie', 60, ''], ['Sessie', 48, ''], ['Beoordelen', 40, ''], ['Scorecontrole', 12, ''], ['Koppeling', 8, ''], ['Publicatie', 148, '']] as $i => [$label, $n, $staat])
                            <a href="#" class="ui-stap {{ $staat ? 'ui-stap--'.$staat : '' }}"><span class="ui-stap__nr">@if ($staat === 'klaar')<x-ui.icoon naam="vink" dikte="3" />@else{{ $i + 1 }}@endif</span><span class="ui-stap__label">{{ $label }}</span><span class="ui-stap__teller">{{ $n }}</span></a>
                        @endforeach
                    </div>
                    <div class="ui-tijdlijn max-w-[520px]">
                        <div class="ui-tijdlijn__item ui-tijdlijn__item--klaar"><span class="ui-tijdlijn__tijd">ma 12 okt · 09:00</span><span class="ui-tijdlijn__titel">Inschrijving geopend</span><p class="ui-tijdlijn__tekst">312 aanmeldingen in de eerste week.</p></div>
                        <div class="ui-tijdlijn__item ui-tijdlijn__item--nu"><span class="ui-tijdlijn__tijd">zo 1 nov · vandaag</span><span class="ui-tijdlijn__titel">Eerste testdag</span><p class="ui-tijdlijn__tekst">Sessie A loopt, sessie B start om 13:00.</p></div>
                        <div class="ui-tijdlijn__item"><span class="ui-tijdlijn__tijd">vr 18 dec · 12:00</span><span class="ui-tijdlijn__titel">Bevriezing</span><p class="ui-tijdlijn__tekst">Definitieve snapshots, badges en persberichten onder embargo.</p></div>
                        <div class="ui-tijdlijn__item"><span class="ui-tijdlijn__tijd">ma 21 dec · 12:00</span><span class="ui-tijdlijn__titel">Hoofdpublicatie</span></div>
                    </div>
                </x-stijlgids.blok>
            </section>
