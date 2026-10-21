            <section id="tabel" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Tabellen</h2>
                <x-stijlgids.blok titel="Tabel met toolbar, sortering, selectie, acties en paginering" klassen=".ui-tabel-wrap .ui-tabel --compact --zebra .ui-paginering" tekst="Filters als pil-dropdowns in de toolbar, kolomkoppen uppercase op zand, rij-acties als icoonknoppen met tooltip, getallen rechts uitgelijnd en tabulair.">
                    <div class="ui-tabel-wrap">
                        <div class="ui-tabel-wrap__toolbar">
                            <div class="ui-segment"><label><input type="radio" name="tab-t" checked>Wachtrij <span class="ui-segment__teller">4</span></label><label><input type="radio" name="tab-t">Afgerond</label><label><input type="radio" name="tab-t">Alles</label></div>
                            <div class="ui-keuze ui-keuze--klein ui-keuze--pil min-w-[150px]" data-ui-keuze data-placeholder="Provincie"><select aria-label="Provincie"><option value="">Provincie</option>@foreach ($provincies as $p)<option>{{ $p }}</option>@endforeach</select></div>
                            <div class="ui-keuze ui-keuze--klein ui-keuze--pil min-w-[130px]" data-ui-keuze data-placeholder="Status"><select aria-label="Status"><option value="">Status</option><option>Ingeschreven</option><option>Ingepland</option><option>Gepubliceerd</option></select></div>
                            <div class="ui-zoek ml-auto w-[240px]"><span class="ui-zoek__icoon"><x-ui.icoon naam="zoek" dikte="2" /></span><input class="ui-invoer ui-invoer--klein !pl-9 !pr-3" placeholder="Zoeken"></div>
                            <button class="ui-knop ui-knop--klein"><x-ui.icoon naam="plus" dikte="2.2" /> Nieuw</button>
                        </div>
                        <div class="ui-tabel-wrap__scroll">
                            <table class="ui-tabel">
                                <thead><tr>
                                    <th class="w-10"><label class="ui-vink"><input type="checkbox" aria-label="Alles selecteren"><span class="ui-vink__vak"><x-ui.icoon naam="vink" dikte="3" /></span></label></th>
                                    <th><button class="ui-tabel__sorteer is-actief">Deelnemer <x-ui.icoon naam="pijl-omlaag" dikte="2.5" /></button></th>
                                    <th><button class="ui-tabel__sorteer">Provincie <x-ui.icoon naam="sorteer" dikte="2.2" /></button></th>
                                    <th>Status</th>
                                    <th class="is-getal"><button class="ui-tabel__sorteer">Cijfer <x-ui.icoon naam="sorteer" dikte="2.2" /></button></th>
                                    <th>Aangeleverd</th>
                                    <th class="is-acties"></th>
                                </tr></thead>
                                <tbody>
                                    @foreach ([
                                        ['Oliebollenkraam De Ketel', 'Haarlem · A07', 'Noord-Holland', 'succes', 'Gepubliceerd', '7,9', 'ma 2 nov, 09:12', true],
                                        ['Bakkerij Zonnebloem', 'Arnhem · B03', 'Gelderland', 'info', 'Ingepland', '–', 'di 3 nov, 10:00', false],
                                        ['Bakkerij Noordwind', 'Alkmaar · A12', 'Noord-Holland', 'waarschuwing', 'Scorecontrole', '8,4', 'ma 2 nov, 09:40', false],
                                        ['Frituur De Waal', 'Nijmegen', 'Gelderland', 'fout', 'Versheid verlopen', '–', 'ma 2 nov, 13:55', false],
                                        ['Bakkerij Het Hoekje', 'Venlo', 'Limburg', 'neutraal', 'Teruggetrokken', '–', '–', false],
                                    ] as [$naam, $sub, $prov, $toon, $status, $cijfer, $tijd, $gekozen])
                                        <tr class="{{ $gekozen ? 'is-gekozen' : '' }}">
                                            <td><label class="ui-vink"><input type="checkbox" @checked($gekozen) aria-label="Selecteer {{ $naam }}"><span class="ui-vink__vak"><x-ui.icoon naam="vink" dikte="3" /></span></label></td>
                                            <td><span class="ui-tabel__primair">{{ $naam }}</span><span class="ui-tabel__sub">{{ $sub }}</span></td>
                                            <td>{{ $prov }}</td>
                                            <td><span class="ui-chip ui-chip--{{ $toon }} ui-chip--klein">{{ $status }}</span></td>
                                            <td class="is-getal">@if ($cijfer !== '–')<span class="ui-score {{ $cijfer === '8,4' ? 'ui-score--top' : '' }}">★ {{ $cijfer }}</span>@else<span class="text-gedempt">–</span>@endif</td>
                                            <td class="text-gedempt">{{ $tijd }}</td>
                                            <td class="is-acties"><span class="ui-tabel__acties"><button class="ui-knop ui-knop--icoon ui-knop--klein ui-knop--ghost" data-tip="Bekijken" aria-label="Bekijken"><x-ui.icoon naam="oog" /></button><button class="ui-knop ui-knop--icoon ui-knop--klein ui-knop--ghost" data-tip="Bewerken" aria-label="Bewerken"><x-ui.icoon naam="potlood" /></button><div class="ui-menu" data-ui-menu><button class="ui-knop ui-knop--icoon ui-knop--klein ui-knop--ghost" aria-label="Meer acties"><x-ui.icoon naam="meer" /></button><div class="ui-menu__paneel ui-menu__paneel--rechts"><button class="ui-menu__item"><x-ui.icoon naam="document" /> Etiket printen</button><button class="ui-menu__item"><x-ui.icoon naam="link" /> Kopiëren naar sessie</button><div class="ui-menu__scheiding"></div><button class="ui-menu__item ui-menu__item--gevaar"><x-ui.icoon naam="prullenbak" /> Terugtrekken</button></div></div></span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="ui-tabel-wrap__voet">
                            <span>Toont 1 tot 5 van 312 · <strong class="text-espresso">1 geselecteerd</strong></span>
                            <nav class="ui-paginering" aria-label="Paginering"><a href="#" aria-disabled="true" aria-label="Vorige"><x-ui.icoon naam="chevron-links" dikte="2.4" /></a><a href="#" class="is-actief">1</a><a href="#">2</a><a href="#">3</a><span>…</span><a href="#">63</a><a href="#" aria-label="Volgende"><x-ui.icoon naam="chevron-rechts" dikte="2.4" /></a></nav>
                        </div>
                    </div>
                </x-stijlgids.blok>
            </section>
