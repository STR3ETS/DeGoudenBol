            <section id="overlays" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Menu, modal, lade</h2>

                <x-stijlgids.blok titel="Actiemenu" klassen="[data-ui-menu] .ui-menu__paneel(--rechts) .ui-menu__item(--gevaar) .ui-menu__kop .ui-menu__scheiding" tekst="Toetsenbord: pijlen, Escape. Sluit bij klik buiten of na een keuze.">
                    <div class="ui-knoppen">
                        <div class="ui-menu" data-ui-menu>
                            <button class="ui-knop ui-knop--secundair">Acties <x-ui.icoon naam="chevron-omlaag" dikte="2.2" /></button>
                            <div class="ui-menu__paneel">
                                <div class="ui-menu__kop">Sessie A</div>
                                <button class="ui-menu__item"><x-ui.icoon naam="herlaad" /> Schema opnieuw genereren</button>
                                <button class="ui-menu__item"><x-ui.icoon naam="document" /> Etiketten printen <kbd>P</kbd></button>
                                <button class="ui-menu__item" aria-disabled="true"><x-ui.icoon naam="vergrendel" /> Sessie sluiten</button>
                                <div class="ui-menu__scheiding"></div>
                                <button class="ui-menu__item ui-menu__item--gevaar"><x-ui.icoon naam="prullenbak" /> Sessie verwijderen</button>
                            </div>
                        </div>
                        <div class="ui-menu" data-ui-menu>
                            <button class="ui-pilknop"><span class="ui-avatar ui-avatar--klein">RM</span><span class="ui-pilknop__tekst">Raphael<small>Beheerder</small></span><x-ui.icoon naam="chevron-omlaag" dikte="2.2" /></button>
                            <div class="ui-menu__paneel">
                                <a class="ui-menu__item" href="#"><x-ui.icoon naam="gebruiker" /> Mijn profiel</a>
                                <a class="ui-menu__item" href="#"><x-ui.icoon naam="instellingen" /> Instellingen</a>
                                <div class="ui-menu__scheiding"></div>
                                <a class="ui-menu__item ui-menu__item--gevaar" href="#"><x-ui.icoon naam="uitloggen" /> Uitloggen</a>
                            </div>
                        </div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Modal en lade" klassen="dialog.ui-modal [data-ui-modal=id] [data-ui-sluit] · dialog.ui-lade" tekst="Native <dialog>: focus-trap, Escape en backdrop gratis. Modal voor bevestigingen en korte formulieren, lade voor dossiers naast een tabel.">
                    <div class="ui-knoppen">
                        <button class="ui-knop" data-ui-modal="demo-modal">Open modal</button>
                        <button class="ui-knop ui-knop--gevaar" data-ui-modal="demo-bevestig">Bevestiging</button>
                        <button class="ui-knop ui-knop--secundair" data-ui-modal="demo-lade">Open lade</button>
                    </div>
                    <dialog id="demo-modal" class="ui-modal">
                        <div class="ui-modal__kop"><span class="ui-icoonvak"><x-ui.icoon naam="kalender" /></span><div><div class="ui-modal__titel">Aanleverslot kiezen</div><div class="ui-modal__sub">Oliebollenkraam De Ketel · Noord-Holland</div></div><button class="ui-knop ui-knop--icoon ui-knop--klein ui-knop--ghost ui-modal__sluit" data-ui-sluit aria-label="Sluiten"><x-ui.icoon naam="kruis" dikte="2.2" /></button></div>
                        <div class="ui-modal__inhoud">
                            <div class="ui-formulier">
                                <div class="ui-veld"><label class="ui-label">Testdag</label><div class="ui-datum" data-ui-datum data-min="{{ now()->toDateString() }}"><input type="hidden" value="{{ now()->addDays(3)->toDateString() }}"></div></div>
                                <div class="ui-veld"><label class="ui-label">Tijdslot</label><div class="ui-keuze" data-ui-keuze><select><option>09:00 – 09:30</option><option>09:30 – 10:00</option><option>10:00 – 10:30</option></select></div></div>
                                <label class="ui-vink ui-formulier__breed"><input type="checkbox" checked><span class="ui-vink__vak"><x-ui.icoon naam="vink" dikte="3" /></span><span class="ui-vink__tekst">Stuur het aanleverbewijs per e-mail</span></label>
                            </div>
                        </div>
                        <div class="ui-modal__voet"><button class="ui-knop ui-knop--ghost" data-ui-sluit>Annuleren</button><button class="ui-knop" data-ui-sluit>Inplannen</button></div>
                    </dialog>
                    <dialog id="demo-bevestig" class="ui-modal ui-modal--klein">
                        <div class="ui-modal__kop"><span class="ui-icoonvak ui-icoonvak--fout"><x-ui.icoon naam="waarschuwing" /></span><div><div class="ui-modal__titel">Inschrijving terugtrekken?</div><div class="ui-modal__sub">Dit kan niet ongedaan worden gemaakt.</div></div></div>
                        <div class="ui-modal__inhoud">De plek in Gelderland komt direct vrij. De deelnemer ontvangt een bevestiging per e-mail.</div>
                        <div class="ui-modal__voet"><button class="ui-knop ui-knop--ghost" data-ui-sluit>Annuleren</button><button class="ui-knop ui-knop--gevaar" data-ui-sluit>Terugtrekken</button></div>
                    </dialog>
                    <dialog id="demo-lade" class="ui-lade">
                        <div class="ui-modal__kop"><span class="ui-avatar">DK</span><div><div class="ui-modal__titel">Oliebollenkraam De Ketel</div><div class="ui-modal__sub">Haarlem · Noord-Holland · testnummer A07</div></div><button class="ui-knop ui-knop--icoon ui-knop--klein ui-knop--ghost ui-modal__sluit" data-ui-sluit aria-label="Sluiten"><x-ui.icoon naam="kruis" dikte="2.2" /></button></div>
                        <div class="ui-modal__inhoud">
                            <div class="mb-4 flex flex-wrap gap-2"><span class="ui-chip ui-chip--succes"><span class="ui-chip__stip"></span>Gepubliceerd</span><span class="ui-score">★ 7,9</span><span class="ui-chip ui-chip--goud">Plaats 1</span></div>
                            <div class="ui-tijdlijn">
                                <div class="ui-tijdlijn__item ui-tijdlijn__item--klaar"><span class="ui-tijdlijn__tijd">12 okt</span><span class="ui-tijdlijn__titel">Aangemeld en betaald</span></div>
                                <div class="ui-tijdlijn__item ui-tijdlijn__item--klaar"><span class="ui-tijdlijn__tijd">2 nov · 09:12</span><span class="ui-tijdlijn__titel">Ontvangen, testnummer A07</span></div>
                                <div class="ui-tijdlijn__item ui-tijdlijn__item--nu"><span class="ui-tijdlijn__tijd">6 nov · 12:00</span><span class="ui-tijdlijn__titel">Gepubliceerd op de Voorlijst</span></div>
                            </div>
                        </div>
                        <div class="ui-modal__voet"><button class="ui-knop ui-knop--ghost" data-ui-sluit>Sluiten</button><button class="ui-knop">Naar het dossier</button></div>
                    </dialog>
                </x-stijlgids.blok>
            </section>
