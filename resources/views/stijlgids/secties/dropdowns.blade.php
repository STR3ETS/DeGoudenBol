            <section id="keuze" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Custom dropdowns</h2>

                <x-stijlgids.blok titel="Enkelvoudig, met zoeken, meervoudig" klassen="[data-ui-keuze] + select" tekst="Een gewone <select> blijft de bron (formulier, validatie, Livewire). ui.js bouwt er een eigen knop en paneel omheen: toetsenbord (pijlen, Enter, Escape), zoeken (data-zoek), meerkeuze met chips (multiple), wisknop (data-wissen), groepen (optgroup), kleurstip (data-kleur) en subregel (data-sub). Zonder JavaScript staat de native select er gewoon.">
                    <div class="ui-formulier">
                        <div class="ui-veld">
                            <label class="ui-label">Provincie</label>
                            <div class="ui-keuze" data-ui-keuze data-placeholder="Kies een provincie">
                                <select name="provincie" aria-label="Provincie">
                                    <option value="">Kies een provincie</option>
                                    @foreach ($provincies as $p)<option value="{{ Str::slug($p) }}" @selected($p === 'Gelderland')>{{ $p }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label">Deelnemer (met zoeken, wisbaar)</label>
                            <div class="ui-keuze" data-ui-keuze data-zoek data-wissen data-placeholder="Zoek een deelnemer">
                                <select name="deelnemer" aria-label="Deelnemer">
                                    <option value=""></option>
                                    <optgroup label="Noord-Holland">
                                        <option value="1" data-sub="Haarlem · testnummer A07">Oliebollenkraam De Ketel</option>
                                        <option value="2" data-sub="Alkmaar · testnummer A12">Bakkerij Noordwind</option>
                                    </optgroup>
                                    <optgroup label="Gelderland">
                                        <option value="3" data-sub="Arnhem · testnummer B03">Bakkerij Zonnebloem</option>
                                        <option value="4" data-sub="Nijmegen · nog niet ontvangen" disabled>Frituur De Waal</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label">Status (meerkeuze)</label>
                            <div class="ui-keuze" data-ui-keuze data-placeholder="Alle statussen">
                                <select name="status[]" multiple aria-label="Status">
                                    <option value="registered" data-kleur="#2C5A7A" selected>Ingeschreven</option>
                                    <option value="scheduled" data-kleur="#2C5A7A">Ingepland</option>
                                    <option value="published" data-kleur="#2F6B3A" selected>Gepubliceerd</option>
                                    <option value="withdrawn" data-kleur="#5A5A6A">Teruggetrokken</option>
                                    <option value="expired" data-kleur="#9B2C2C">Versheid verlopen</option>
                                </select>
                            </div>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label">Klein en als pil (filterbalk)</label>
                            <div class="flex flex-wrap gap-3">
                                <div class="ui-keuze ui-keuze--klein ui-keuze--pil min-w-[160px]" data-ui-keuze data-placeholder="Type">
                                    <select aria-label="Type"><option value="">Type</option><option>Bakkerij</option><option>Kraam</option><option>Frituur</option></select>
                                </div>
                                <div class="ui-keuze ui-keuze--klein ui-keuze--pil min-w-[160px]" data-ui-keuze data-placeholder="Ronde">
                                    <select aria-label="Ronde" disabled><option value="">Ronde</option><option>Provinciaal</option></select>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-stijlgids.blok>
            </section>
