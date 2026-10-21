            <section id="knoppen" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Knoppen</h2>

                <x-stijlgids.blok titel="Varianten" klassen=".ui-knop --secundair --omlijnd --ghost --gevaar --donker" tekst="Primair is goud met espresso tekst; bij hover schuift espresso in. Eén primaire knop per scherm.">
                    <div class="ui-knoppen">
                        <button class="ui-knop">Primair</button>
                        <button class="ui-knop ui-knop--secundair">Secundair</button>
                        <button class="ui-knop ui-knop--omlijnd">Omlijnd</button>
                        <button class="ui-knop ui-knop--ghost">Ghost</button>
                        <button class="ui-knop ui-knop--gevaar">Verwijderen</button>
                        <button class="ui-knop ui-knop--donker">Donker</button>
                        <button class="ui-knop" disabled>Uitgeschakeld</button>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Maten, iconen, laden" klassen="--klein --groot --icoon --rond --breed --laden">
                    <div class="ui-knoppen">
                        <button class="ui-knop ui-knop--klein">Klein</button>
                        <button class="ui-knop">Normaal</button>
                        <button class="ui-knop ui-knop--groot">Groot</button>
                        <button class="ui-knop"><x-ui.icoon naam="plus" dikte="2.2" /> Nieuwe sessie</button>
                        <button class="ui-knop ui-knop--secundair">Exporteren <x-ui.icoon naam="download" dikte="2" /></button>
                        <button class="ui-knop ui-knop--icoon ui-knop--secundair" data-tip="Bewerken" aria-label="Bewerken"><x-ui.icoon naam="potlood" /></button>
                        <button class="ui-knop ui-knop--icoon ui-knop--rond" aria-label="Toevoegen"><x-ui.icoon naam="plus" dikte="2.2" /></button>
                        <button class="ui-knop ui-knop--icoon ui-knop--klein ui-knop--ghost" aria-label="Meer"><x-ui.icoon naam="meer" /></button>
                        <button class="ui-knop ui-knop--laden" aria-busy="true">Opslaan</button>
                    </div>
                    <div class="mt-4 max-w-[320px]"><button class="ui-knop ui-knop--breed">Brede knop</button></div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Op donker" :donker="true" klassen=".ui-knop / --omlijnd op espresso">
                    <div class="ui-knoppen">
                        <button class="ui-knop">Primair</button>
                        <button class="ui-knop ui-knop--omlijnd !border-room-decor !text-room-zacht hover:!bg-goud hover:!text-espresso">Omlijnd licht</button>
                        <button class="ui-knop ui-knop--ghost !text-room-zacht hover:!bg-[rgba(250,245,234,0.08)] hover:!text-room">Ghost licht</button>
                    </div>
                </x-stijlgids.blok>
            </section>
