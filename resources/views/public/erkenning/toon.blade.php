@php use App\Support\DutchTime; @endphp
<x-layouts.public :title="$recognition->badgeText()" :description="$recognition->company->name.': '.$recognition->badgeText().'. Geverifieerde erkenning van De Gouden Bol.'">
    <section class="relative overflow-hidden pt-36 pb-16">
        <div class="watermerk pointer-events-none absolute top-1/2 -right-10 -translate-y-1/2 text-[clamp(8rem,16vw,14rem)] !opacity-[0.05]" aria-hidden="true">{{ $recognition->edition->year }}</div>
        <div class="site-container relative grid gap-10 lg:grid-cols-[1fr_360px]">
            <div>
                <x-ui.eyebrow>{{ $historic ? 'Historische erkenning' : 'Geverifieerde erkenning' }}</x-ui.eyebrow>
                <h1 class="kop-pagina mb-4">{{ $recognition->type->getLabel() }}<br><em>{{ $recognition->company->name }}</em></h1>
                <p class="body-text max-w-[600px]">
                    @if ($historic)
                        Deze erkenning uit De Gouden Bol {{ $recognition->edition->year }} is historisch: ze gold tot {{ DutchTime::date($recognition->valid_until) }}. Een nieuwe editie brengt nieuwe uitslagen.
                    @else
                        Deze erkenning is echt en actueel. {{ $recognition->company->name }} heeft in De Gouden Bol {{ $recognition->edition->year }} de erkenning "{{ $recognition->type->getLabel() }}" behaald{{ $recognition->province ? ' in '.$recognition->province->name : '' }}. Blind beoordeeld door het onafhankelijke panel; hangt nooit aan een pakket of betaling.
                    @endif
                </p>
                <dl class="mt-8 grid max-w-[520px] gap-x-8 gap-y-2 text-[0.9rem] sm:grid-cols-[160px_1fr]">
                    <dt class="text-gedempt">Bedrijf</dt><dd class="font-semibold">{{ $recognition->company->name }}{{ $recognition->company->primaryLocation ? ', '.$recognition->company->primaryLocation->city : '' }}</dd>
                    <dt class="text-gedempt">Categorie</dt><dd class="font-semibold">{{ $recognition->badgeText() }}</dd>
                    <dt class="text-gedempt">Editie</dt><dd class="font-semibold">{{ $recognition->edition->year }}</dd>
                    <dt class="text-gedempt">Geldig</dt><dd class="font-semibold">{{ DutchTime::date($recognition->valid_from) }}{{ $recognition->valid_until ? ' t/m '.DutchTime::date($recognition->valid_until) : ' tot de uitslag van de volgende editie' }}</dd>
                    <dt class="text-gedempt">Verificatiecode</dt><dd class="font-mono font-semibold tracking-[0.12em]">{{ $recognition->code }}</dd>
                </dl>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-ui.button :href="route('bakkers.toon', $recognition->company)">Bekijk het profiel</x-ui.button>
                    @if ($recognition->province)
                        <x-ui.button variant="outline" :href="route('provincies.show', $recognition->province)">Voorlijst {{ $recognition->province->name }}</x-ui.button>
                    @endif
                </div>
            </div>
            <aside class="flex flex-col gap-4">
                <x-ui.card class="!p-4">
                    <img src="{{ $recognition->badgeUrl('licht') }}" alt="{{ $recognition->badgeText() }} (licht)" width="600" height="200" class="h-auto w-full rounded-veld">
                </x-ui.card>
                <x-ui.card variant="espresso" class="!p-4">
                    <img src="{{ $recognition->badgeUrl('donker') }}" alt="{{ $recognition->badgeText() }} (donker)" width="600" height="200" class="h-auto w-full rounded-veld">
                </x-ui.card>
                <p class="text-[0.78rem] text-gedempt">Badges van De Gouden Bol worden altijd vanaf ons platform geladen en linken naar deze pagina. <a href="{{ route('erkenning.zoek') }}" class="text-goud-tekst underline">Andere code controleren</a></p>
            </aside>
        </div>
    </section>
</x-layouts.public>
