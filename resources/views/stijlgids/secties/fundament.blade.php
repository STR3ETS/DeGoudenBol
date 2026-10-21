            <section id="fundament" class="mb-20 scroll-mt-28">
                <h2 class="kop-2 mb-8">Fundament</h2>

                <x-stijlgids.blok titel="Kleuren" tekst="Uit resources/design/tokens.json. Goud is accent; gouden tekst op licht is altijd goud-tekst (AA).">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($kleuren as [$naam, $hex, $gebruik])
                            <div class="ui-kaart overflow-hidden">
                                <div class="h-16" style="background: {{ $hex }}"></div>
                                <div class="p-3">
                                    <div class="flex items-center justify-between text-[0.8rem]"><strong>{{ $naam }}</strong><code class="text-gedempt">{{ $hex }}</code></div>
                                    <p class="mt-1 text-[0.72rem] leading-snug text-gedempt">{{ $gebruik }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        @foreach ($statussen as [$naam, $tekst, $bg, $gebruik])
                            <div class="rounded-blok p-4" style="background: {{ $bg }}; color: {{ $tekst }}">
                                <div class="text-[0.72rem] font-extrabold uppercase tracking-[0.12em]">{{ $naam }}</div>
                                <p class="mt-1 text-[0.72rem] leading-snug opacity-90">{{ $gebruik }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Typografie" tekst="Cormorant Garamond voor koppen en grote cijfers (altijd lining numerals), Manrope voor alle interface-tekst.">
                    <div class="flex flex-col gap-4">
                        <div class="kop-display">Display <em>H1</em> 300</div>
                        <div class="kop-pagina">Pagina H1 <em>300</em></div>
                        <div class="kop-2">H2 600 met <em>cursief goud</em></div>
                        <div class="kop-3">H3 / kaarttitel 600</div>
                        <div class="ui-tegel__cijfer">2.026,5</div>
                        <div class="ui-eyebrow">Eyebrow · Manrope 700 · 0,24em</div>
                        <p class="body-text max-w-[600px]">Body · Manrope 400 · regelhoogte 1,82 · kleur gedempt. De blinde keuring beoordeelt op acht onderdelen van een 100-puntenmodel.</p>
                        <div class="ui-label">Formulierlabel · 0,68rem · uppercase</div>
                        <p class="text-[0.74rem] text-gedempt">Meta · 0,74rem · gedempt</p>
                        <p class="citaat max-w-[520px]">"Citaat of jurynotitie in Cormorant italic."</p>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Vorm en schaduw" tekst="Radius: pil 99 · veld 12 · blok 16 · kaart-in-kaart 20 · kaart 24 · paneel 28. Schaduwen zijn warm getint en zacht.">
                    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6 text-center text-[0.7rem] font-bold text-gedempt">
                        <div><div class="mx-auto mb-2 h-16 w-16 rounded-pil bg-wit shadow-[var(--shadow-zwevend)]"></div>pil</div>
                        <div><div class="mx-auto mb-2 h-16 w-16 rounded-veld bg-wit shadow-[var(--shadow-zwevend)]"></div>veld 12</div>
                        <div><div class="mx-auto mb-2 h-16 w-16 rounded-blok bg-wit shadow-[var(--shadow-zwevend)]"></div>blok 16</div>
                        <div><div class="mx-auto mb-2 h-16 w-16 rounded-kaart-in-kaart bg-wit shadow-[var(--shadow-zwevend)]"></div>kaart-in-kaart 20</div>
                        <div><div class="mx-auto mb-2 h-16 w-16 rounded-kaart bg-wit shadow-[var(--shadow-kaart-hover)]"></div>kaart 24</div>
                        <div><div class="mx-auto mb-2 h-16 w-16 rounded-paneel bg-goud shadow-[var(--shadow-knop-hover)]"></div>paneel 28</div>
                    </div>
                </x-stijlgids.blok>

                <x-stijlgids.blok titel="Iconen" klassen="x-ui.icoon naam=…" tekst="Lijn-iconen op een 24-raster, lijndikte 1,8. Altijd met een tekstlabel of aria-label.">
                    <div class="flex flex-wrap gap-2">
                        @foreach (['zoek', 'kalender', 'klok', 'chevron-omlaag', 'chevron-rechts', 'pijl-rechts', 'vink', 'kruis', 'plus', 'bel', 'gebruiker', 'instellingen', 'huis', 'document', 'euro', 'ster', 'info', 'waarschuwing', 'upload', 'filter', 'oog', 'potlood', 'prullenbak', 'meer', 'bliksem', 'vlag', 'cadeau', 'hart', 'winkel', 'klembord', 'kaart', 'uitloggen', 'link', 'kopieer', 'download', 'vergrendel', 'sorteer', 'herlaad'] as $icoon)
                            <span class="ui-icoonvak" data-tip="{{ $icoon }}" tabindex="0"><x-ui.icoon :naam="$icoon" /></span>
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach (['goud', 'espresso', 'succes', 'info', 'waarschuwing', 'fout', 'neutraal'] as $toon)
                            <span class="ui-icoonvak ui-icoonvak--{{ $toon }}"><x-ui.icoon naam="ster" /></span>
                        @endforeach
                        <span class="ui-icoonvak ui-icoonvak--rond"><x-ui.icoon naam="bel" /></span>
                        <span class="ui-icoonvak ui-icoonvak--klein"><x-ui.icoon naam="vink" /></span>
                    </div>
                </x-stijlgids.blok>
            </section>
