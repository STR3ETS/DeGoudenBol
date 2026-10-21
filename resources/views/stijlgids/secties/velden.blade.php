            <section id="velden" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Velden</h2>

                <x-stijlgids.blok titel="Invoer, tekstvak, native select" klassen=".ui-veld .ui-label .ui-invoer .ui-hulp .ui-fout" tekst="Hoogte 44 px, radius 12, rand invoer-rand, focus goud met zachte ring. Labels uppercase. Foutstaat op de wrapper (.ui-veld--fout).">
                    <div class="ui-formulier">
                        <div class="ui-veld">
                            <label class="ui-label" for="v1">Bedrijfsnaam <span class="ui-label__verplicht">*</span></label>
                            <input id="v1" class="ui-invoer" placeholder="Bakkerij…">
                            <p class="ui-hulp">Zoals ingeschreven bij de KvK.</p>
                        </div>
                        <div class="ui-veld ui-veld--fout">
                            <label class="ui-label" for="v2">E-mailadres <span class="ui-label__verplicht">*</span></label>
                            <input id="v2" class="ui-invoer" value="geen-adres" aria-invalid="true">
                            <p class="ui-fout"><x-ui.icoon naam="uitroep" dikte="2.2" /> Dit is geen geldig e-mailadres.</p>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label" for="v3">Met icoon</label>
                            <div class="ui-invoer-wrap"><span class="ui-invoer-wrap__icoon"><x-ui.icoon naam="gebruiker" /></span><input id="v3" class="ui-invoer" placeholder="Contactpersoon"></div>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label" for="v4">Bedrag <span class="ui-label__optioneel">(excl. btw)</span></label>
                            <div class="ui-invoer-wrap ui-invoer-wrap--achter"><input id="v4" class="ui-invoer" value="745,00"><span class="ui-invoer-wrap__toevoeging">EUR</span></div>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label" for="v5">Native select</label>
                            <select id="v5" class="ui-invoer ui-invoer--select"><option>Bakkerij</option><option>Oliebollenkraam</option><option>Frituur</option></select>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label" for="v6">Uitgeschakeld</label>
                            <input id="v6" class="ui-invoer" value="Niet aanpasbaar" disabled>
                        </div>
                        <div class="ui-veld ui-formulier__breed">
                            <label class="ui-label" for="v7">Motivatie</label>
                            <textarea id="v7" class="ui-invoer" placeholder="Waarom dit goede doel?"></textarea>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label" for="v8">Zandvariant (in filterbalk)</label>
                            <input id="v8" class="ui-invoer ui-invoer--zand" placeholder="Zoek op naam of plaats">
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label" for="v9">Klein (in tabellen)</label>
                            <input id="v9" class="ui-invoer ui-invoer--klein" value="Compact">
                        </div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Zoekpil" klassen=".ui-zoek .ui-zoek__icoon .ui-zoek__toets">
                    <div class="ui-zoek max-w-[420px]"><span class="ui-zoek__icoon"><x-ui.icoon naam="zoek" dikte="2" /></span><input class="ui-invoer" placeholder="Zoek bakker, testnummer, order…"><span class="ui-zoek__toets">Ctrl+K</span></div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Vinkjes, radio's, schakelaars" klassen=".ui-vink .ui-radio .ui-schakelaar" tekst="Eigen vormgegeven, met native inputs eronder (toetsenbord en screenreaders blijven werken).">
                    <div class="grid gap-6 md:grid-cols-3">
                        <div class="ui-veldgroep">
                            <div class="ui-veldgroep__titel">Allergenen</div>
                            <label class="ui-vink"><input type="checkbox" checked><span class="ui-vink__vak"><x-ui.icoon naam="vink" dikte="3" /></span><span class="ui-vink__tekst">Gluten</span></label>
                            <label class="ui-vink"><input type="checkbox"><span class="ui-vink__vak"><x-ui.icoon naam="vink" dikte="3" /></span><span class="ui-vink__tekst">Melk <small>Inclusief boter en room</small></span></label>
                            <label class="ui-vink"><input type="checkbox" disabled><span class="ui-vink__vak"><x-ui.icoon naam="vink" dikte="3" /></span><span class="ui-vink__tekst">Noten (uitgeschakeld)</span></label>
                        </div>
                        <div class="ui-veldgroep">
                            <div class="ui-veldgroep__titel">Ronde</div>
                            <label class="ui-radio"><input type="radio" name="ronde" checked><span class="ui-radio__vak"></span><span class="ui-radio__tekst">Provinciaal</span></label>
                            <label class="ui-radio"><input type="radio" name="ronde"><span class="ui-radio__vak"></span><span class="ui-radio__tekst">Beslisronde</span></label>
                            <label class="ui-radio"><input type="radio" name="ronde"><span class="ui-radio__vak"></span><span class="ui-radio__tekst">Finale</span></label>
                        </div>
                        <div class="ui-veldgroep">
                            <div class="ui-veldgroep__titel">Instellingen</div>
                            <label class="ui-schakelaar"><input type="checkbox" checked><span class="ui-schakelaar__spoor"></span>Paginacache aan</label>
                            <label class="ui-schakelaar"><input type="checkbox"><span class="ui-schakelaar__spoor"></span>Toon panel na editie</label>
                            <label class="ui-schakelaar"><input type="checkbox" disabled><span class="ui-schakelaar__spoor"></span>Uitgeschakeld</label>
                        </div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Gesegmenteerde keuze, stapper, tijd" klassen=".ui-segment .ui-stapper .ui-tijd">
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="ui-segment" role="radiogroup" aria-label="Werkvoorraad">
                            <label><input type="radio" name="seg" checked>Wachtrij <span class="ui-segment__teller">12</span></label>
                            <label><input type="radio" name="seg">Afgerond</label>
                            <label><input type="radio" name="seg">Alles</label>
                        </div>
                        <div class="ui-segment ui-segment--goud" role="radiogroup" aria-label="Periode">
                            <label><input type="radio" name="seg2" checked>Dag</label>
                            <label><input type="radio" name="seg2">Week</label>
                            <label><input type="radio" name="seg2">Maand</label>
                        </div>
                        <div class="ui-stapper" data-ui-stapper><button type="button" aria-label="Minder">−</button><input type="number" value="8" min="1" max="12" aria-label="Aantal"><button type="button" aria-label="Meer">+</button></div>
                        <div class="ui-tijd"><input type="time" class="ui-invoer" value="12:00" aria-label="Tijd"></div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Bestanden" klassen=".ui-upload .ui-bestand">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="ui-upload"><input type="file"><span class="ui-upload__icoon"><x-ui.icoon naam="upload" dikte="2" /></span><span><strong>Sleep een logo hierheen</strong> of klik om te kiezen</span><span class="text-[0.72rem]">PNG of SVG, max. 2 MB</span></label>
                        <div class="flex flex-col gap-2">
                            <div class="ui-bestand"><x-ui.icoon naam="document" class="h-4 w-4 text-goud-tekst" /> logo-meelfabriek.svg <span class="ui-bestand__meta">14 kB</span><button class="ui-knop ui-knop--icoon ui-knop--klein ui-knop--ghost" aria-label="Verwijderen"><x-ui.icoon naam="kruis" dikte="2.2" /></button></div>
                            <div class="ui-bestand"><x-ui.icoon naam="document" class="h-4 w-4 text-goud-tekst" /> monster-A07.jpg <span class="ui-bestand__meta">1,2 MB</span><span class="ui-laden"></span></div>
                        </div>
                    </div>
                </x-stijlgids.blok>
            </section>
