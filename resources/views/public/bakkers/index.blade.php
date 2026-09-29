@php $year = $edition?->year ?? now()->year; @endphp
<x-layouts.public title="Alle bakkers" description="Alle deelnemende bakkers en kramen van De Gouden Bol, te filteren op provincie en type bedrijf.">
    <section class="relative overflow-hidden pt-36 pb-12">
        <div class="watermerk pointer-events-none absolute top-1/2 -right-10 -translate-y-1/2 text-[clamp(8rem,16vw,14rem)] !opacity-[0.05]" aria-hidden="true">BAKKERS</div>
        <div class="site-container relative">
            <x-ui.eyebrow>Deelnemers {{ $year }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Alle deelnemende <em>bakkers</em><br>van Nederland</h1>
            <p class="body-text max-w-[520px]">Klik op een bakker voor het profiel, de standplaats en de openingstijden. Cijfers verschijnen zodra de Voorlijst is gepubliceerd.</p>
        </div>
    </section>

    <form method="get" action="{{ route('bakkers.index') }}" class="sticky top-[72px] z-[800] border-y border-rand-sterk bg-zand py-4" role="search">
        <div class="site-container flex flex-wrap items-center gap-2.5">
            <span class="mr-1 text-[0.72rem] font-bold tracking-[0.1em] text-gedempt uppercase">Filter</span>
            <a href="{{ route('bakkers.index', array_filter(['q' => $filters['q'] ?? null, 'type' => $filters['type'] ?? null])) }}" class="chip {{ empty($filters['provincie']) ? 'chip--solid' : '' }}">Alle provincies</a>
            @foreach ($provinces as $province)
                <a href="{{ route('bakkers.index', array_filter(['provincie' => $province->slug, 'q' => $filters['q'] ?? null, 'type' => $filters['type'] ?? null])) }}" class="chip {{ ($filters['provincie'] ?? null) === $province->slug ? 'chip--solid' : '' }}">{{ $province->name }}</a>
            @endforeach
            <div class="ml-auto flex w-full items-center gap-2 sm:w-auto">
                @if (! empty($filters['provincie']))
                    <input type="hidden" name="provincie" value="{{ $filters['provincie'] }}">
                @endif
                <select name="type" class="input input--select !min-h-0 !w-auto !py-2 text-[0.8rem]" aria-label="Type bedrijf" onchange="this.form.submit()">
                    <option value="">Alle types</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input !min-h-0 !w-[220px] !rounded-pil !py-2 text-[0.82rem]" placeholder="Zoek op naam of plaats…" aria-label="Zoeken">
                <button type="submit" class="btn-pill btn--sm">Zoek</button>
            </div>
        </div>
    </form>

    <section class="section--sub py-14">
        <div class="site-container">
            @if ($entries === null || $entries->isEmpty())
                <x-ui.empty-state titel="Geen bakkers gevonden" tekst="Er zijn nog geen bevestigde deelnemers die aan deze filters voldoen.">
                    @if (! empty(array_filter($filters)))
                        <x-ui.button variant="outline" :href="route('bakkers.index')">Alle filters wissen</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <p class="mb-7 text-[0.85rem] text-gedempt"><strong class="text-espresso">{{ $entries->total() }}</strong> {{ $entries->total() === 1 ? 'bakker' : 'bakkers' }} gevonden</p>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($entries as $entry)
                        <x-bakker-kaart :entry="$entry" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $entries->links() }}</div>
            @endif
        </div>
    </section>
</x-layouts.public>
