            <section id="kaarten" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Kaarten en tegels</h2>

                <x-stijlgids.blok titel="Kaart met kop, inhoud, voet" klassen=".ui-kaart --zand --espresso --klik .ui-kaart__kop .ui-kaart__inhoud .ui-kaart__voet">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="ui-kaart">
                            <div class="ui-kaart__kop"><span class="ui-icoonvak"><x-ui.icoon naam="bliksem" /></span><div><div class="ui-kaart__titel">Jouw werk vandaag</div><div class="ui-kaart__sub">5 taken</div></div><div class="ui-kaart__acties"><button class="ui-knop ui-knop--icoon ui-knop--klein ui-knop--ghost" aria-label="Meer"><x-ui.icoon naam="meer" /></button></div></div>
                            <div class="ui-kaart__inhoud text-[0.85rem] text-gedempt">Inhoud van de kaart. Padding 1,25 × 1,35 rem.</div>
                            <div class="ui-kaart__voet">Bijgewerkt zojuist <a class="ml-auto font-bold text-goud-tekst" href="#">Alles →</a></div>
                        </div>
                        <div class="ui-kaart ui-kaart--zand ui-kaart--klik"><div class="ui-kaart__inhoud"><div class="ui-eyebrow">Zand · klikbaar</div><div class="ui-kaart__titel">Provinciepartner</div><p class="mt-1 text-[0.82rem] text-gedempt">Hover tilt de kaart 3 px op met gouden schaduw.</p></div></div>
                        <div class="ui-kaart ui-kaart--espresso"><div class="ui-kaart__inhoud"><div class="ui-eyebrow !text-goud">Espresso</div><div class="ui-kaart__titel">Provinciewinnaar 2026</div><p class="mt-1 text-[0.82rem] text-room-zacht">Voor uitgelichte blokken en embargo's.</p></div></div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Kerncijfertegels" klassen=".ui-tegel --goud --succes --info --waarschuwing --fout --espresso --mini" tekst="Pastel tint, groot Cormorant-cijfer, link en watermerk-icoon. Dit vervangt de huidige dashboardtegels.">
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <a href="#" class="ui-tegel ui-tegel--goud"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="klembord" /></span>Bevestigde deelnemers</div><div class="ui-tegel__cijfer">312</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk alle <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>van 600 plekken · 4 vandaag</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="klembord" dikte="1.2" /></span></a>
                        <a href="#" class="ui-tegel ui-tegel--waarschuwing"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--waarschuwing"><x-ui.icoon naam="klok" /></span>Wacht op betaling</div><div class="ui-tegel__cijfer">7</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk alle <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>reservering nog geldig</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="klok" dikte="1.2" /></span></a>
                        <a href="#" class="ui-tegel ui-tegel--succes"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--succes"><x-ui.icoon naam="euro" /></span>Omzet deelname</div><div class="ui-tegel__cijfer">€ 232.440</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Bekijk alle <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>excl. btw</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="euro" dikte="1.2" /></span></a>
                        <a href="#" class="ui-tegel ui-tegel--espresso"><div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="ster" /></span>Gepubliceerd</div><div class="ui-tegel__cijfer">148</div><div class="ui-tegel__voet"><span class="ui-tegel__link">Voorlijst <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>in 12 provincies</div><span class="ui-tegel__watermerk"><x-ui.icoon naam="ster" dikte="1.2" /></span></a>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        <div class="ui-tegel ui-tegel--mini"><div class="ui-label">Deelname</div><div class="ui-tegel__cijfer">€ 1.490</div><div class="ui-tegel__voet">1 open</div></div>
                        <div class="ui-tegel ui-tegel--mini"><div class="ui-label">Sponsoring</div><div class="ui-tegel__cijfer">€ 300</div><div class="ui-tegel__voet">niets open</div></div>
                        <div class="ui-tegel ui-tegel--mini ui-tegel--info"><div class="ui-label">Gereserveerd</div><div class="ui-tegel__cijfer">€ 179</div><div class="ui-tegel__voet">10 % goede doelen</div></div>
                        <div class="ui-tegel ui-tegel--mini ui-tegel--fout"><div class="ui-label">Versheid</div><div class="ui-tegel__cijfer">2</div><div class="ui-tegel__voet">verlopen vandaag</div></div>
                        <div class="ui-tegel ui-tegel--mini ui-tegel--succes"><div class="ui-label">Kaarten</div><div class="ui-tegel__cijfer">48</div><div class="ui-tegel__voet">ingediend</div></div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Stats, staven, balken, avatars, datumblok" klassen=".ui-stat .ui-staven .ui-balk .ui-avatar(s) .ui-datumblok">
                    <div class="grid gap-6 md:grid-cols-3">
                        <div class="flex flex-col gap-4">
                            <div class="ui-stat"><div class="ui-stat__cijfer">2.418</div><div class="ui-stat__label">Scorekaarten</div><div class="ui-stat__delta"><x-ui.icoon naam="pijl-omhoog" dikte="2.5" /> +12 % t.o.v. vorige week</div></div>
                            <div class="ui-stat"><div class="ui-stat__cijfer">7,9</div><div class="ui-stat__label">Gemiddeld cijfer</div><div class="ui-stat__delta is-neer"><x-ui.icoon naam="pijl-omlaag" dikte="2.5" /> −0,2</div></div>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="ui-staven" aria-label="Aanmeldingen per dag">@foreach ([20, 35, 50, 30, 70, 90, 45, 60, 80, 55, 100, 65, 40, 75] as $i => $h)<span class="ui-staven__staaf {{ $i === 13 ? 'ui-staven__staaf--vandaag' : '' }}" title="{{ $h }}"><span class="ui-staven__vulling" style="height: {{ $h }}%"></span></span>@endforeach</div>
                            <div><div class="mb-1 flex justify-between text-[0.74rem] font-bold"><span>Gelderland</span><span class="text-gedempt">38 / 50</span></div><div class="ui-balk"><div class="ui-balk__vulling" style="width: 76%"></div></div></div>
                            <div><div class="mb-1 flex justify-between text-[0.74rem] font-bold"><span>Zeeland</span><span class="text-gedempt">9 / 50</span></div><div class="ui-balk"><div class="ui-balk__vulling" style="width: 18%"></div></div></div>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-3"><span class="ui-avatar">RM</span><span class="ui-avatar ui-avatar--espresso">BV</span><span class="ui-avatar ui-avatar--zand ui-avatar--klein">JK</span><span class="ui-avatars"><span class="ui-avatar ui-avatar--klein">A</span><span class="ui-avatar ui-avatar--klein ui-avatar--espresso">B</span><span class="ui-avatar ui-avatar--klein ui-avatar--zand">+4</span></span></div>
                            <div class="flex items-center gap-3"><span class="ui-datumblok"><span class="ui-datumblok__dag">ma</span><span class="ui-datumblok__nr">12</span><span class="ui-datumblok__maand">okt</span></span><span class="ui-datumblok ui-datumblok--goud"><span class="ui-datumblok__dag">zo</span><span class="ui-datumblok__nr">1</span><span class="ui-datumblok__maand">nov</span></span><span class="text-[0.85rem] text-gedempt">Mijlpalen met datumblok</span></div>
                        </div>
                    </div>
                </x-stijlgids.blok>
            </section>
