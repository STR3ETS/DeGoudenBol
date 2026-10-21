            <section id="navigatie" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Navigatie</h2>
                <x-stijlgids.blok titel="Sidebar, topbar, pilnavigatie, filterbalk" klassen=".ui-sidebar .ui-topbar .ui-pilknop .ui-pilnav .ui-filterbalk">
                    <div class="grid gap-6 lg:grid-cols-[272px_1fr]">
                        <div class="ui-sidebar">
                            <a href="#" class="ui-sidebar__merk"><x-ui.logo :dark="true" tagline="Backoffice" /></a>
                            <a href="#" class="ui-sidebar__item is-actief"><span class="ui-icoonvak"><x-ui.icoon naam="huis" /></span>Overzicht</a>
                            <div class="ui-sidebar__groep">Bakkers</div>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="winkel" /></span>Bedrijven</a>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="klembord" /></span>Inschrijvingen <span class="ui-badge">3</span></a>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="gebruiker" /></span>Accounts</a>
                            <div class="ui-sidebar__groep">Testdagen</div>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="kalender" /></span>Testsessies</a>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="ster" /></span>Scorecontrole <span class="ui-badge">12</span></a>
                            <div class="ui-sidebar__voet"><a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="instellingen" /></span>Instellingen</a></div>
                        </div>
                        <div class="flex flex-col gap-6">
                            <div class="ui-topbar">
                                <div class="ui-topbar__begroeting"><span class="ui-topbar__kop">Goedemorgen, Raphael 👋</span><span class="ui-topbar__sub">Donderdag 8 oktober 2026 · De Gouden Bol 2026 · Inschrijving open</span></div>
                                <div class="ui-zoek hidden w-[260px] xl:flex"><span class="ui-zoek__icoon"><x-ui.icoon naam="zoek" dikte="2" /></span><input class="ui-invoer" placeholder="Zoek…"><span class="ui-zoek__toets">Ctrl+K</span></div>
                                <button class="ui-pilknop" aria-label="Meldingen"><x-ui.icoon naam="bel" /><span class="ui-badge">3</span></button>
                                <button class="ui-pilknop"><span class="ui-avatar ui-avatar--klein">RM</span><span class="ui-pilknop__tekst hidden sm:flex">Raphael<small>Beheerder</small></span><x-ui.icoon naam="chevron-omlaag" dikte="2.2" /></button>
                            </div>
                            <nav class="ui-pilnav" aria-label="Portaal"><a href="#" class="is-actief">Overzicht</a><a href="#">Profiel</a><a href="#">Planning</a><a href="#">Uitslag</a><a href="#">Marketing</a><a href="#">Cadeaubonnen</a><a href="#">Facturen</a></nav>
                            <div class="ui-filterbalk">
                                <span class="ui-tag is-actief">Alle</span><span class="ui-tag">Bakkerij</span><span class="ui-tag">Kraam</span><span class="ui-tag">Frituur</span>
                                <div class="ui-keuze ui-keuze--klein ui-keuze--pil min-w-[160px]" data-ui-keuze data-placeholder="Provincie"><select aria-label="Provincie"><option value="">Provincie</option>@foreach ($provincies as $p)<option>{{ $p }}</option>@endforeach</select></div>
                                <div class="ui-zoek ml-auto w-[220px]"><span class="ui-zoek__icoon"><x-ui.icoon naam="zoek" dikte="2" /></span><input class="ui-invoer ui-invoer--klein !pl-9 !pr-3" placeholder="Zoek bakker"></div>
                                <span class="text-[0.74rem] font-bold text-gedempt">312 resultaten</span>
                            </div>
                            <div class="ui-paginakop"><div><span class="ui-eyebrow">Testdagen</span><h3 class="ui-paginakop__titel">Test<em>sessies</em></h3><p class="ui-paginakop__sub">Plan sessies, genereer het uitserveerschema en volg de versheid.</p></div><div class="ui-knoppen"><button class="ui-knop ui-knop--secundair"><x-ui.icoon naam="download" dikte="2" /> Export</button><button class="ui-knop"><x-ui.icoon naam="plus" dikte="2.2" /> Nieuwe sessie</button></div></div>
                        </div>
                    </div>
                </x-stijlgids.blok>
            </section>
