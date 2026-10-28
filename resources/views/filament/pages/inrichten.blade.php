@php
    $checklist = $this->checklist();
    $registration = $checklist->registration();
    $items = $checklist->items();
    $progress = $checklist->progress();
    $pct = $progress['total'] > 0 ? (int) round($progress['done'] / $progress['total'] * 100) : 0;
    $firstOpen = collect($items)->first(fn (array $item) => ! $item['done'] && ! $item['optional']);
@endphp
<x-filament-panels::page>
    <div class="ui-dash">
        <header class="ui-app__kop">
            <x-ui.bol class="ui-bol--klein" :stemming="$registration['state'] === 'open' ? 'trots' : 'blij'" />
            <div class="ui-app__kop-tekst">
                <h1 class="ui-app__titel">Inrichten</h1>
                <p class="ui-app__sub">Alles wat klaar moet staan om het seizoen te starten, in de goede volgorde. Eén knop om de inschrijving open te zetten.</p>
            </div>
        </header>

        <section class="ui-vandaag ui-app__blok" aria-labelledby="inrichten-status">
            <div>
                <div class="ui-vandaag__eyebrow"><x-ui.icoon naam="zon" /> Inschrijving</div>
                <h2 id="inrichten-status" class="ui-vandaag__titel">
                    @if ($registration['state'] === 'open')
                        De inschrijving is <em>open</em>.
                    @elseif ($registration['state'] === 'blocked')
                        De inschrijving staat open, maar de aanmeldpagina is <em>dicht</em>.
                    @elseif ($registration['state'] === 'none')
                        Er is nog <em>geen editie</em>.
                    @else
                        De inschrijving is <em>dicht</em>.
                    @endif
                </h2>
                <p class="ui-vandaag__tekst">{{ $registration['text'] }}</p>
                @if ($registration['reasons'] !== [])
                    <ul class="mt-3 flex flex-col gap-1 text-[0.9rem] text-room-zacht">
                        @foreach ($registration['reasons'] as $reason)
                            <li class="flex gap-2"><x-ui.icoon :naam="$registration['state'] === 'blocked' ? 'waarschuwing' : 'chevron-rechts'" class="mt-1 h-4 w-4 flex-none text-goud" /> {{ ucfirst($reason) }}.</li>
                        @endforeach
                    </ul>
                @endif
                <div class="ui-vandaag__acties">
                    @if ($registration['can_open'])
                        <button type="button" class="ui-knop ui-knop--groot" wire:click="mountAction('openRegistration')">Inschrijving nu openzetten <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></button>
                    @elseif ($registration['state'] === 'closed' && $firstOpen !== null && $firstOpen['url'])
                        <a {{ \Filament\Support\generate_href_html($firstOpen['url']) }} class="ui-knop ui-knop--groot">Volgende stap: {{ lcfirst($firstOpen['title']) }} <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
                    @endif
                    @if ($registration['state'] === 'open')
                        <a href="{{ $registration['url'] }}" target="_blank" rel="noopener" class="ui-knop ui-knop--groot">Bekijk de aanmeldpagina <x-ui.icoon naam="link" dikte="2.4" /></a>
                    @endif
                    @if ($registration['can_close'])
                        <button type="button" class="ui-knop ui-knop--groot ui-knop--ghost" wire:click="mountAction('closeRegistration')">Inschrijving sluiten</button>
                    @endif
                </div>
                @if ($registration['state'] === 'open' || $registration['state'] === 'blocked')
                    <details class="ui-waarom mt-4">
                        <summary>Hoe werkt open en dicht? <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary>
                        <p>De datums bij de editie bepalen het: open vanaf "Inschrijving opent", dicht vanaf "Inschrijving sluit" (of anders na de laatste testdag). De knoppen hier zetten die datums op nu. Daarnaast moeten de voorwaarden gepubliceerd zijn, anders blijft de aanmeldpagina dicht.</p>
                    </details>
                @endif
            </div>
            <div class="ui-vandaag__zij">
                <div class="ui-ring ui-ring--donker ui-ring--groot" style="--ring-pct: {{ $pct }}"><span class="ui-ring__tekst"><span class="ui-ring__cijfer">{{ $progress['done'] }}<span class="text-[1.1rem] text-room-zacht">/{{ $progress['total'] }}</span></span><span class="ui-ring__label">stappen klaar</span></span></div>
                <span class="text-[0.78rem] text-room-zacht">{{ $progress['done'] === $progress['total'] ? 'Alles staat klaar' : 'Verplichte stappen' }}</span>
            </div>
        </section>

        <div class="ui-sectie">
            <h2 class="ui-sectie__titel">De checklist <span class="ui-sectie__sub">Groen is klaar. Werk van boven naar beneden; de stappen met "later" hoeven pas vóór de eerste testdag.</span></h2>
        </div>
        <div class="ui-app__blok grid gap-3 xl:grid-cols-2">
            @foreach ($items as $item)
                @php
                    $isTermsPublish = $item['key'] === 'voorwaarden' && ! $item['done'] && $item['action'] === 'Concept publiceren';
                    $tag = $item['url'] && ! $isTermsPublish ? 'a' : ($isTermsPublish ? 'button' : 'div');
                @endphp
                <{{ $tag }}
                    @if ($tag === 'a') {{ \Filament\Support\generate_href_html($item['url']) }} @endif
                    @if ($tag === 'button') type="button" wire:click="mountAction('publishTerms')" @endif
                    class="ui-actie {{ $item['done'] ? 'ui-actie--klaar' : '' }} {{ ! $item['done'] && $item['optional'] ? 'ui-actie--later !opacity-100' : '' }}"
                >
                    <span class="ui-icoonvak ui-icoonvak--{{ $item['done'] ? 'succes' : ($item['optional'] ? 'neutraal' : 'goud') }}"><x-filament::icon :icon="$item['icon']" /></span>
                    <span class="ui-actie__tekst">
                        <span class="ui-actie__titel">{{ $item['title'] }}@if (! $item['done'] && $item['optional']) <span class="ui-chip ui-chip--klein ui-chip--neutraal ml-2 align-middle">later</span>@endif</span>
                        <span class="ui-actie__sub">{{ $item['text'] }}</span>
                    </span>
                    @if ($item['done'])
                        <span class="ui-actie__pijl" aria-label="Klaar"><x-ui.icoon naam="vink" dikte="3" /></span>
                    @elseif ($item['url'] || $isTermsPublish)
                        <span class="ui-actie__pijl" aria-hidden="true"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>
                    @else
                        <span class="ui-actie__pijl !bg-zand !text-gedempt" aria-label="Serverinstelling"><x-ui.icoon naam="instellingen" /></span>
                    @endif
                </{{ $tag }}>
            @endforeach
        </div>

        <div class="ui-hulpkaart">
            <div class="ui-hulpkaart__kop"><x-ui.icoon naam="vraag" class="h-5 w-5 text-goud-tekst" /> Zo werkt het seizoen</div>
            <p class="ui-hulpkaart__tekst">Eerst richt je in (deze pagina), dan zet je de inschrijving open. Bakkers melden zich aan en kiezen een aanlevermoment. Op de testdagen proeft het panel en controleer jij de scores. Na de bevriezing publiceer je de uitslag met twee goedkeurders. Alles wat per jaar verschilt, staat bij Instellingen; de code hoef je niet aan te raken.</p>
            <a href="{{ route('docs') }}" class="ui-hulpkaart__link">Open de handleiding <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
        </div>
    </div>
</x-filament-panels::page>
