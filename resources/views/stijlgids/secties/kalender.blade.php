            <section id="datum" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Kalender en datum</h2>

                <x-stijlgids.blok titel="Datumveld met kalender" klassen="[data-ui-datum] + input[type=hidden]" tekst="Nederlandse maand- en dagnamen, week begint op maandag, vandaag omlijnd goud, gekozen dag solid goud. Grenzen via data-min/data-max, markeringen via data-markeer (bv. testdagen), snelkeuzes via data-snel, periode via data-bereik met een tweede verborgen input [data-einde]. Pijltjestoetsen bewegen door de maand.">
                    <div class="ui-formulier">
                        <div class="ui-veld">
                            <label class="ui-label">Aanleverdatum</label>
                            <div class="ui-datum" data-ui-datum data-min="{{ now()->toDateString() }}" data-max="{{ now()->addMonths(3)->toDateString() }}" data-markeer="{{ now()->addDays(3)->toDateString() }},{{ now()->addDays(10)->toDateString() }},{{ now()->addDays(17)->toDateString() }}" data-snel data-wissen>
                                <input type="hidden" name="aanleverdatum" value="{{ now()->addDays(3)->toDateString() }}">
                            </div>
                            <p class="ui-hulp">Gouden stip = testdag met vrije slots.</p>
                        </div>
                        <div class="ui-veld">
                            <label class="ui-label">Periode (sponsorplaatsing)</label>
                            <div class="ui-datum" data-ui-datum data-bereik data-placeholder="Van – tot en met">
                                <input type="hidden" name="van" value="">
                                <input type="hidden" name="tot" data-einde value="">
                            </div>
                        </div>
                        <div class="ui-veld ui-veld--fout">
                            <label class="ui-label">Met fout</label>
                            <div class="ui-datum" data-ui-datum><input type="hidden" name="fout" value=""></div>
                            <p class="ui-fout"><x-ui.icoon naam="uitroep" dikte="2.2" /> Kies een datum na de eerste testdag.</p>
                        </div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Kalender inline" klassen="[data-ui-kalender] (.ui-kalender / --breed)" tekst="Zelfde kalender als losse component, bijvoorbeeld in een cockpit of planningspagina. Kiezen vuurt het event ui:datum op het element en vult optioneel een input (data-invoer).">
                    <div class="grid gap-6 md:grid-cols-[auto_1fr] items-start">
                        <div class="ui-kaart p-4 inline-block"><div class="ui-kalender" data-ui-kalender data-value="{{ now()->toDateString() }}" data-markeer="{{ now()->addDays(2)->toDateString() }},{{ now()->addDays(5)->toDateString() }}"></div></div>
                        <div class="ui-kaart p-4"><div class="ui-kalender ui-kalender--breed" data-ui-kalender data-bereik data-value="{{ now()->addDays(1)->toDateString() }}" data-einde="{{ now()->addDays(6)->toDateString() }}" data-snel data-wissen></div></div>
                    </div>
                </x-stijlgids.blok>
            </section>
