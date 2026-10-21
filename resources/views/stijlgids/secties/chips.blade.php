            <section id="chips" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Chips, badges, tags, scores</h2>

                <x-stijlgids.blok titel="Statuschips" klassen=".ui-chip --succes --waarschuwing --fout --info --neutraal --goud --espresso --omlijnd --klein" tekst="Gedaald is nooit rood: neutraal. Met stip voor live-status, met kruisje als verwijderbaar filter.">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="ui-chip">Standaard</span>
                        <span class="ui-chip ui-chip--succes"><span class="ui-chip__stip"></span>Gepubliceerd</span>
                        <span class="ui-chip ui-chip--waarschuwing"><span class="ui-chip__stip"></span>Wacht op goedkeuring</span>
                        <span class="ui-chip ui-chip--fout"><span class="ui-chip__stip"></span>Versheid verlopen</span>
                        <span class="ui-chip ui-chip--info">Ingepland</span>
                        <span class="ui-chip ui-chip--neutraal">Gedaald</span>
                        <span class="ui-chip ui-chip--goud"><x-ui.icoon naam="ster" dikte="2.2" /> Provinciewinnaar</span>
                        <span class="ui-chip ui-chip--espresso">Embargo</span>
                        <span class="ui-chip ui-chip--omlijnd">Concept</span>
                        <span class="ui-chip ui-chip--klein ui-chip--succes">Klein</span>
                        <span class="ui-chip ui-chip--goud">Gelderland <button class="ui-chip__verwijder" aria-label="Filter verwijderen"><x-ui.icoon naam="kruis" dikte="3" /></button></span>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Badges, tags, scores" klassen=".ui-badge .ui-tag .ui-score">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="ui-badge">3</span>
                        <span class="ui-badge ui-badge--espresso">12</span>
                        <span class="ui-badge ui-badge--fout">!</span>
                        <span class="ml-4 ui-tag is-actief">Alle provincies</span>
                        <span class="ui-tag">Noord-Holland</span>
                        <span class="ui-tag"><x-ui.icoon naam="filter" /> Filters <span class="ui-badge">2</span></span>
                        <span class="ml-4 ui-score ui-score--top">★ 9,4</span>
                        <span class="ui-score">★ 7,9</span>
                    </div>
                </x-stijlgids.blok>
            </section>
