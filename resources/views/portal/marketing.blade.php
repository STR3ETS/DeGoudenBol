@php use App\Support\DutchTime; @endphp
<x-layouts.portal title="Marketing">
    <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-3">Badges en <em>socialkit</em></h1>
    <p class="body-text mb-8 max-w-[640px]">Iedere behaalde erkenning levert een badge voor uw website (met verificatielink) en een socialkit met beeld in vier formaten en kant-en-klare teksten. Badges hangen aan de uitslag, nooit aan een pakket.</p>

    @if ($pressRelease ?? null)
        <x-ui.card class="mb-8 flex flex-wrap items-center justify-between gap-4" variant="zand">
            <div>
                <x-ui.eyebrow>Persbericht van uw provincie</x-ui.eyebrow>
                <div class="kop-3 text-[1.15rem]">{{ $pressRelease->title }}</div>
                <p class="mt-1 text-[0.85rem] text-gedempt">Gebruik de tekst voor uw eigen regionale media; de link is openbaar.</p>
            </div>
            <x-ui.button :href="route('pers.toon', $pressRelease)" variant="outline" size="sm">Persbericht openen</x-ui.button>
        </x-ui.card>
    @endif

    @if ($entry === null)
        <x-ui.empty-state titel="Geen inschrijving voor deze editie" />
    @elseif ($reached->isEmpty())
        <x-ui.empty-state titel="Nog geen erkenning" tekst="Zodra uw deelname is bevestigd verschijnt hier de badge 'Deelnemer'; na publicatie van uw cijfer volgt 'Officieel getest'." />
    @else
        <div class="flex flex-col gap-8" data-marketing data-track-url="{{ route('portaal.marketing.meting', $company) }}">
            @foreach ($reached as $item)
                @php
                    $milestone = $item['milestone'];
                    $recognition = $item['recognition'];
                    $available = $item['available_from'] === null;
                @endphp
                <x-ui.card>
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <x-ui.eyebrow>{{ $milestone->getLabel() }}</x-ui.eyebrow>
                            <h2 class="kop-3">{{ $recognition?->badgeText() ?? $milestone->headline($entry->province?->name) }}</h2>
                        </div>
                        @if (! $available)
                            <x-ui.chip status="waarschuwing" :dot="true">Onder embargo tot {{ DutchTime::format($item['available_from'], 'D MMMM, HH:mm') }}</x-ui.chip>
                        @elseif ($recognition)
                            <x-ui.chip status="succes" :dot="true">Geverifieerd · code {{ $recognition->code }}</x-ui.chip>
                        @endif
                    </div>

                    @if (! $available)
                        <p class="text-[0.9rem] text-gedempt">Badge en socialkit komen beschikbaar op het moment van de reveal. Tot die tijd is dit resultaat vertrouwelijk: deel het nog niet.</p>
                    @else
                        @if ($recognition)
                            <div class="mb-6 grid gap-4 md:grid-cols-2">
                                <div class="rounded-kaart border border-rand p-4">
                                    <img src="{{ $recognition->badgeUrl('licht') }}" alt="{{ $recognition->badgeText() }} (licht)" width="600" height="200" class="h-auto w-full">
                                    <div class="mt-3 flex flex-wrap gap-2 text-[0.8rem]">
                                        <a href="{{ $recognition->badgeUrl('licht') }}" download class="font-bold text-goud-tekst underline">Download SVG (licht)</a>
                                        <a href="{{ $recognition->verificationUrl() }}" target="_blank" rel="noopener" class="text-gedempt underline">Verificatiepagina</a>
                                    </div>
                                </div>
                                <div class="rounded-kaart bg-espresso p-4">
                                    <img src="{{ $recognition->badgeUrl('donker') }}" alt="{{ $recognition->badgeText() }} (donker)" width="600" height="200" class="h-auto w-full">
                                    <div class="mt-3 text-[0.8rem]"><a href="{{ $recognition->badgeUrl('donker') }}" download class="font-bold text-goud underline">Download SVG (donker)</a></div>
                                </div>
                            </div>
                            <label class="label" for="embed-{{ $recognition->code }}">Embedcode voor uw website (badge met verificatielink)</label>
                            <div class="mb-6 flex flex-wrap gap-2">
                                <textarea id="embed-{{ $recognition->code }}" class="input min-w-[260px] flex-1 font-mono !text-[0.75rem]" rows="3" readonly>{{ $recognition->embedCode('licht') }}</textarea>
                                <x-ui.button type="button" variant="outline" size="sm" data-copy="#embed-{{ $recognition->code }}" data-milestone="{{ $milestone->value }}" data-kind="copy" data-format="embed">Kopiëren</x-ui.button>
                            </div>
                        @endif

                        <h3 class="mb-3 font-kop text-[1.15rem] font-semibold">Socialkit</h3>
                        <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($formats as $format => [$width, $height, $label])
                                <a href="{{ route('portaal.marketing.beeld', [$company, $milestone->value, $format]) }}" download="de-gouden-bol-{{ $milestone->value }}-{{ $format }}.svg" class="block rounded-veld border border-rand bg-zand p-2 no-underline" data-track data-milestone="{{ $milestone->value }}" data-kind="download" data-format="{{ $format }}">
                                    <img src="{{ route('portaal.marketing.beeld', [$company, $milestone->value, $format]) }}" alt="{{ $milestone->getLabel() }}, {{ $label }}" class="mx-auto h-[140px] w-auto object-contain" loading="lazy">
                                    <span class="mt-2 block text-center text-[0.72rem] font-bold text-espresso">{{ $label }} · {{ $width }}×{{ $height }}</span>
                                </a>
                            @endforeach
                        </div>
                        <label class="label" for="tekst-{{ $milestone->value }}">Tekst</label>
                        <textarea id="tekst-{{ $milestone->value }}" class="input mb-3 !text-[0.85rem]" rows="5" readonly>{{ $texts[$milestone->value] ?? '' }}</textarea>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.button type="button" size="sm" data-share data-share-title="{{ $milestone->headline($entry->province?->name) }}" data-share-text="{{ $texts[$milestone->value] ?? '' }}" data-share-url="{{ $profileUrl }}" data-milestone="{{ $milestone->value }}">Delen</x-ui.button>
                            <x-ui.button type="button" variant="outline" size="sm" data-copy="#tekst-{{ $milestone->value }}" data-milestone="{{ $milestone->value }}" data-kind="copy" data-format="text">Tekst kopiëren</x-ui.button>
                            <x-ui.button variant="outline" size="sm" :href="route('portaal.marketing.kit', [$company, $milestone->value])">Alles als zip</x-ui.button>
                        </div>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    @endif
</x-layouts.portal>
