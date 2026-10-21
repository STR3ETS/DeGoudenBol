            <section id="dashboard" class="mb-10 scroll-mt-28">
                <h2 class="kop-2 mb-3">Voorbeeld dashboard</h2>
                <p class="body-text mb-8 max-w-[720px]">Zo ziet het overzicht van de backoffice eruit in de nieuwe stijl: alles uit deze bibliotheek, niets extra's. Dit is de maat voor het omgooien van het dashboard.</p>
                <div class="rounded-paneel bg-zand p-4 sm:p-6">
                    <div class="grid gap-5 lg:grid-cols-[272px_1fr]">
                        <div class="ui-sidebar hidden lg:flex">
                            <a href="#" class="ui-sidebar__merk"><x-ui.logo :dark="true" tagline="Backoffice" /></a>
                            <a href="#" class="ui-sidebar__item is-actief"><span class="ui-icoonvak"><x-ui.icoon naam="huis" /></span>Overzicht</a>
                            <div class="ui-sidebar__groep">Bakkers</div>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="winkel" /></span>Bedrijven</a>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="klembord" /></span>Inschrijvingen <span class="ui-badge">3</span></a>
                            <div class="ui-sidebar__groep">Testdagen</div>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="kalender" /></span>Testsessies</a>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="ster" /></span>Scorecontrole <span class="ui-badge">12</span></a>
                            <div class="ui-sidebar__groep">Uitslag</div>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="document" /></span>Publicaties</a>
                            <div class="ui-sidebar__groep">Geld</div>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="euro" /></span>Orders</a>
                            <a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="hart" /></span>Goede doelen</a>
                            <div class="ui-sidebar__voet"><a href="#" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="instellingen" /></span>Instellingen</a></div>
                        </div>
                        <div class="flex min-w-0 flex-col gap-5">
                            <div class="ui-topbar !bg-transparent !p-0">
                                <div class="ui-topbar__begroeting"><span class="ui-topbar__kop">Goedemorgen, Raphael 👋</span><span class="ui-topbar__sub">Donderdag 8 oktober 2026 · De Gouden Bol 2026 · Inschrijving open</span></div>
                                <button class="ui-pilknop" aria-label="Meldingen"><x-ui.icoon naam="bel" /><span class="ui-badge">3</span></button>
                                <button class="ui-pilknop"><span class="ui-avatar ui-avatar--klein">RM</span><x-ui.icoon naam="chevron-omlaag" dikte="2.2" /></button>
                            </div>
                            <div class="ui-kaart">
                                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="vlag" /></span><div><div class="ui-kaart__titel">Inschrijving loopt</div><div class="ui-kaart__sub">eerste testdag zondag 1 november</div></div><div class="ui-kaart__acties"><span class="ui-chip ui-chip--goud">Editie 2026</span></div></div>
                                <div class="ui-kaart__inhoud !pt-3"><div class="ui-stappen">@foreach ([['Aanmelding', 312, 'klaar'], ['Inplannen', 280, 'actief'], ['Ontvangst', 0, ''], ['Registratie', 0, ''], ['Sessie', 0, ''], ['Beoordelen', 0, ''], ['Scorecontrole', 0, ''], ['Koppeling', 0, ''], ['Publicatie', 0, '']] as $i => [$label, $n, $staat])<a href="#" class="ui-stap {{ $staat ? 'ui-stap--'.$staat : '' }}"><span class="ui-stap__nr">@if ($staat === 'klaar')<x-ui.icoon naam="vink" dikte="3" />@else{{ $i + 1 }}@endif</span><span class="ui-stap__label">{{ $label }}</span><span class="ui-stap__teller">{{ $n }}</span></a>@endforeach</div></div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                                <a href="#" class="ui-tegel ui-tegel--goud"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="klembord" /></span>Bevestigde deelnemers</div><div class="ui-tegel__cijfer">312</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk alle <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>van 600 plekken</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="klembord" dikte="1.2" /></span></a>
                                <a href="#" class="ui-tegel ui-tegel--waarschuwing"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--waarschuwing"><x-ui.icoon naam="klok" /></span>Wacht op betaling</div><div class="ui-tegel__cijfer">7</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk alle <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>reservering geldig</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="klok" dikte="1.2" /></span></a>
                                <a href="#" class="ui-tegel ui-tegel--succes"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--succes"><x-ui.icoon naam="euro" /></span>Omzet deelname</div><div class="ui-tegel__cijfer">€ 232.440</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk alle <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>excl. btw</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="euro" dikte="1.2" /></span></a>
                                <a href="#" class="ui-tegel ui-tegel--info"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--info"><x-ui.icoon naam="document" /></span>Profielen te modereren</div><div class="ui-tegel__cijfer">0</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk alle <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>alles beoordeeld</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="document" dikte="1.2" /></span></a>
                            </div>
                            <div class="grid gap-5 2xl:grid-cols-[3fr_2fr]">
                                <div class="ui-kaart">
                                    <div class="ui-kaart__kop"><span class="ui-icoonvak"><x-ui.icoon naam="bliksem" /></span><div><div class="ui-kaart__titel">Jouw werk vandaag</div><div class="ui-kaart__sub">Wat nu op jou wacht, met de knop erbij</div></div><div class="ui-kaart__acties"><span class="ui-badge ui-badge--espresso">5</span></div></div>
                                    <div class="ui-kaart__inhoud !pt-4"><div class="ui-lijst">
                                        <a href="#" class="ui-rij ui-rij--goud ui-rij--klik"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="kalender" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Testdag vandaag</span><span class="ui-rij__sub">1 sessie · 0 aanleverslots</span></span><span class="ui-rij__meta"><button class="ui-knop ui-knop--klein">Naar de testdag</button></span></a>
                                        <a href="#" class="ui-rij ui-rij--succes ui-rij--klik"><span class="ui-icoonvak ui-icoonvak--succes"><x-ui.icoon naam="klembord" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Sessie A · 09:23</span><span class="ui-rij__sub">2 monsters · 6 panelleden · loopt</span></span><span class="ui-rij__meta"><span class="ui-badge ui-badge--espresso">2</span><button class="ui-knop ui-knop--klein ui-knop--secundair">Bekijken</button></span></a>
                                        <a href="#" class="ui-rij ui-rij--waarschuwing ui-rij--klik"><span class="ui-icoonvak ui-icoonvak--waarschuwing"><x-ui.icoon naam="cadeau" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Winnaars invoeren</span><span class="ui-rij__sub">1 actie · uiterlijk zaterdag 10 oktober om 09:53</span></span><span class="ui-rij__meta"><button class="ui-knop ui-knop--klein ui-knop--secundair">Invoeren</button></span></a>
                                        <a href="#" class="ui-rij ui-rij--info ui-rij--klik"><span class="ui-icoonvak ui-icoonvak--info"><x-ui.icoon naam="euro" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Openstaande orders</span><span class="ui-rij__sub">1 order wacht op betaling</span></span><span class="ui-rij__meta"><button class="ui-knop ui-knop--klein ui-knop--secundair">Afboeken</button></span></a>
                                    </div></div>
                                </div>
                                <div class="ui-kaart">
                                    <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--espresso"><x-ui.icoon naam="vlag" /></span><div><div class="ui-kaart__titel">Mijlpalen 2026</div><div class="ui-kaart__sub">Wat er komt</div></div><div class="ui-kaart__acties"><a href="#" class="text-[0.78rem] font-bold text-goud-tekst">Instellen →</a></div></div>
                                    <div class="ui-kaart__inhoud !pt-4"><div class="ui-lijst">
                                        <div class="ui-rij ui-rij--goud"><span class="ui-datumblok ui-datumblok--goud"><span class="ui-datumblok__dag">ma</span><span class="ui-datumblok__nr">12</span><span class="ui-datumblok__maand">okt</span></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Inschrijving opent</span><span class="ui-rij__sub">om 09:00 uur</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--goud ui-chip--klein">over 4 dgn</span></span></div>
                                        <div class="ui-rij"><span class="ui-datumblok"><span class="ui-datumblok__dag">zo</span><span class="ui-datumblok__nr">1</span><span class="ui-datumblok__maand">nov</span></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Eerste testdag</span><span class="ui-rij__sub">hele dag</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--klein">over 24 dgn</span></span></div>
                                        <div class="ui-rij"><span class="ui-datumblok"><span class="ui-datumblok__dag">vr</span><span class="ui-datumblok__nr">18</span><span class="ui-datumblok__maand">dec</span></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Bevriezing</span><span class="ui-rij__sub">om 12:00 uur</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--klein">over 71 dgn</span></span></div>
                                        <div class="ui-rij"><span class="ui-datumblok"><span class="ui-datumblok__dag">ma</span><span class="ui-datumblok__nr">21</span><span class="ui-datumblok__maand">dec</span></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Hoofdpublicatie</span><span class="ui-rij__sub">om 12:00 uur</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--klein">over 74 dgn</span></span></div>
                                    </div></div>
                                </div>
                            </div>
                            <div class="grid gap-5 2xl:grid-cols-[3fr_2fr]">
                                <div class="ui-kaart">
                                    <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--info"><x-ui.icoon naam="klembord" /></span><div><div class="ui-kaart__titel">Recente aanmeldingen</div><div class="ui-kaart__sub">3 aanmeldingen in 14 dagen</div></div><div class="ui-kaart__acties"><a href="#" class="text-[0.78rem] font-bold text-goud-tekst">Alles</a></div></div>
                                    <div class="ui-kaart__inhoud !pt-4"><div class="ui-staven mb-4" aria-hidden="true">@foreach ([0, 10, 0, 0, 30, 0, 0, 0, 60, 0, 20, 0, 100, 40] as $i => $h)<span class="ui-staven__staaf {{ $i === 13 ? 'ui-staven__staaf--vandaag' : '' }}"><span class="ui-staven__vulling" style="height: {{ $h }}%"></span></span>@endforeach</div><div class="ui-lijst">
                                        <a href="#" class="ui-rij ui-rij--klik"><span class="ui-avatar">BZ</span><span class="ui-rij__tekst"><span class="ui-rij__titel">[DEMO] Bakkerij Zonnebloem</span><span class="ui-rij__sub">Gelderland · 21 uur geleden</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--info ui-chip--klein">Ingepland</span></span></a>
                                        <a href="#" class="ui-rij ui-rij--klik"><span class="ui-avatar ui-avatar--espresso">DK</span><span class="ui-rij__tekst"><span class="ui-rij__titel">[DEMO] Oliebollenkraam De Ketel</span><span class="ui-rij__sub">Noord-Holland · 21 uur geleden</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--succes ui-chip--klein">Gepubliceerd</span></span></a>
                                        <a href="#" class="ui-rij ui-rij--klik"><span class="ui-avatar ui-avatar--zand">FH</span><span class="ui-rij__tekst"><span class="ui-rij__titel">[DEMO] Frituur Het Hoekje</span><span class="ui-rij__sub">Limburg · 21 uur geleden</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--waarschuwing ui-chip--klein">Wacht op betaling</span></span></a>
                                    </div></div>
                                </div>
                                <div class="ui-kaart">
                                    <div class="ui-kaart__kop"><span class="ui-icoonvak"><x-ui.icoon naam="kaart" /></span><div><div class="ui-kaart__titel">Plekken per provincie</div><div class="ui-kaart__sub">Bezet van beschikbaar</div></div></div>
                                    <div class="ui-kaart__inhoud !pt-4 flex flex-col gap-3">@foreach ([['Gelderland', 38], ['Noord-Holland', 47], ['Utrecht', 50], ['Limburg', 12], ['Zeeland', 9]] as [$p, $n])<div><div class="mb-1 flex justify-between text-[0.76rem] font-bold"><span>{{ $p }}</span><span class="text-gedempt">{{ $n }} / 50</span></div><div class="ui-balk"><div class="ui-balk__vulling {{ $n >= 50 ? '!bg-status-fout' : ($n >= 45 ? '!bg-status-waarschuwing' : '') }}" style="width: {{ $n * 2 }}%"></div></div></div>@endforeach</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
