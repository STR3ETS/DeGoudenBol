@php use App\Support\DutchTime; @endphp
<x-layouts.public title="Voorlijst per provincie" description="De Voorlijst van De Gouden Bol per provincie: de blind beoordeelde oliebollen van Nederland, openbaar vanaf een cijfer van 5,0.">
    <x-slot:head>
        <x-ui.json-ld :data="[
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Voorlijst per provincie – De Gouden Bol '.($edition?->year ?? now()->year),
            'itemListElement' => $provinces->values()->map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $p->name, 'url' => route('provincies.show', $p)])->all(),
        ]" />
    </x-slot:head>

    <section class="relative overflow-hidden pt-36 pb-16">
        <div class="watermerk pointer-events-none absolute top-1/2 -right-10 -translate-y-1/2 text-[clamp(8rem,16vw,14rem)] !opacity-[0.05]" aria-hidden="true">VOORLIJST</div>
        <div class="site-container relative">
            <x-ui.eyebrow>Voorlijst {{ $edition?->year ?? '' }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Twaalf provincies, <em>twaalf lijsten</em></h1>
            <p class="body-text max-w-[560px]">
                @if ($edition?->first_test_day)
                    Per provincie verschijnt vanaf {{ DutchTime::date($edition->first_test_day) }} de Voorlijst met blind beoordeelde oliebollen. Tot die tijd ziet u hier de aangemelde deelnemers en de beschikbare plekken.
                @else
                    Per provincie verschijnt hier de Voorlijst met blind beoordeelde oliebollen.
                @endif
            </p>
        </div>
    </section>

    <section class="section--sub bg-zand py-16">
        <div class="site-container grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($provinces as $province)
                @php $stats = $availability[$province->slug] ?? null; @endphp
                <a href="{{ route('provincies.show', $province) }}" class="card card--hover relative block no-underline">
                    <div class="watermerk absolute top-3 right-4 text-[3.2rem]">{{ $province->code }}</div>
                    <x-ui.chip status="neutraal" class="mb-3">Nog geen resultaten</x-ui.chip>
                    <div class="font-kop text-kaarttitel font-semibold text-espresso">{{ $province->name }}</div>
                    <div class="text-[0.78rem] text-gedempt">
                        @if ($stats)
                            {{ $stats['taken'] }} aangemeld · {{ $stats['available'] }} van {{ $stats['capacity'] }} plekken vrij
                        @endif
                    </div>
                    <div class="mt-4 text-[0.78rem] font-bold text-goud-tekst">Bekijk provincie &rarr;</div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.public>
