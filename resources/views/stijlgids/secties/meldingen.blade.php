            <section id="feedback" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Meldingen en feedback</h2>

                <x-stijlgids.blok titel="Inline meldingen" klassen=".ui-melding --succes --waarschuwing --fout --neutraal --embargo">
                    <div class="flex flex-col gap-3">
                        <div class="ui-melding"><span class="ui-melding__icoon"><x-ui.icoon naam="info" dikte="2" /></span><div class="ui-melding__inhoud"><span class="ui-melding__titel">Volgende publicatie vrijdag 12:00</span>Goedgekeurde batches gaan automatisch mee.</div><button class="ui-melding__sluit" aria-label="Sluiten"><x-ui.icoon naam="kruis" dikte="2.2" /></button></div>
                        <div class="ui-melding ui-melding--succes"><span class="ui-melding__icoon"><x-ui.icoon naam="vink" dikte="2.5" /></span><div class="ui-melding__inhoud"><span class="ui-melding__titel">Batch gepubliceerd</span>12 uitslagen staan op de Voorlijst.</div></div>
                        <div class="ui-melding ui-melding--waarschuwing"><span class="ui-melding__icoon"><x-ui.icoon naam="waarschuwing" dikte="2" /></span><div class="ui-melding__inhoud"><span class="ui-melding__titel">Versheid verloopt over 24 minuten</span>Monster A07 moet vóór 14:10 beoordeeld zijn.</div></div>
                        <div class="ui-melding ui-melding--fout"><span class="ui-melding__icoon"><x-ui.icoon naam="uitroep" dikte="2.2" /></span><div class="ui-melding__inhoud"><span class="ui-melding__titel">Publicatie geblokkeerd</span>Twee verschillende goedkeurders nodig; de indiener keurt nooit zelf.</div></div>
                        <div class="ui-melding ui-melding--neutraal"><span class="ui-melding__icoon"><x-ui.icoon naam="klok" dikte="2" /></span><div class="ui-melding__inhoud">Historisch: editie 2025 is gearchiveerd.</div></div>
                        <div class="ui-melding ui-melding--embargo"><span class="ui-melding__icoon"><x-ui.icoon naam="vergrendel" dikte="2" /></span><div class="ui-melding__inhoud"><span class="ui-melding__titel">Onder embargo tot maandag 21 december, 12:00</span>Vertrouwelijk; niet delen tot de reveal.</div></div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Toasts" klassen="uiToast({ titel, tekst, status }) · [data-ui-toast]" tekst="Rechtsonder, espresso, verdwijnen na 5 seconden. Eén regel bevestiging, nooit een uitleg.">
                    <div class="ui-knoppen">
                        <button class="ui-knop ui-knop--secundair ui-knop--klein" data-ui-toast="succes" data-titel="Opgeslagen" data-tekst="De scorekaart is ingediend.">Succes</button>
                        <button class="ui-knop ui-knop--secundair ui-knop--klein" data-ui-toast="info" data-titel="Perskit verstuurd" data-tekst="Naar 14 contacten.">Info</button>
                        <button class="ui-knop ui-knop--secundair ui-knop--klein" data-ui-toast="waarschuwing" data-titel="Nog 1 goedkeuring nodig">Waarschuwing</button>
                        <button class="ui-knop ui-knop--secundair ui-knop--klein" data-ui-toast="fout" data-titel="Niet opgeslagen" data-tekst="Controleer de gemarkeerde velden.">Fout</button>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Tooltip, voortgang, laden, skelet, lege staat" klassen="[data-tip] .ui-voortgang .ui-laden .ui-laadpil .ui-skelet .ui-lege-staat">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="flex flex-col gap-5">
                            <div class="flex items-center gap-3"><button class="ui-knop ui-knop--secundair ui-knop--klein" data-tip="Dit is een tooltip">Hover mij</button><span class="ui-laden"></span><span class="ui-laadpil"><span class="ui-laden"></span>Pagina laden…</span></div>
                            <div class="ui-voortgang"><div class="ui-voortgang__kop"><span>Gelderland</span><span>38 / 50</span></div><div class="ui-voortgang__balk"><div class="ui-voortgang__vulling" style="width: 76%"></div></div></div>
                            <div class="ui-voortgang ui-voortgang--waarschuwing"><div class="ui-voortgang__kop"><span>Noord-Holland</span><span>47 / 50</span></div><div class="ui-voortgang__balk"><div class="ui-voortgang__vulling" style="width: 94%"></div></div></div>
                            <div class="ui-voortgang ui-voortgang--fout"><div class="ui-voortgang__kop"><span>Utrecht</span><span>50 / 50</span></div><div class="ui-voortgang__balk"><div class="ui-voortgang__vulling" style="width: 100%"></div></div></div>
                            <div class="flex flex-col gap-2"><span class="ui-skelet h-4 w-2/3"></span><span class="ui-skelet h-4 w-1/2"></span><span class="ui-skelet h-10 w-full"></span></div>
                        </div>
                        <div class="ui-lege-staat"><span class="ui-lege-staat__icoon"><x-ui.icoon naam="klembord" /></span><div class="ui-lege-staat__titel">Nog geen gepubliceerde resultaten</div><p class="ui-lege-staat__tekst">De Voorlijst van Drenthe verschijnt na de eerste publicatie op vrijdag 12:00.</p><button class="ui-knop ui-knop--klein mt-2">Naar de publicaties</button></div>
                    </div>
                </x-stijlgids.blok>
            </section>
