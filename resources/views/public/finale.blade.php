@php
    use App\Support\DutchTime;

    $year = $edition?->year ?? now()->year;
    $winner = $positions->first();
@endphp
<x-layouts.public :title="'Landelijke finale '.$year" :description="$winner ? 'De beste oliebol van Nederland '.$year.' komt van '.$winner->entry->public_name.' ('.$winner->entry->province->name.'), volgens de blinde finale van De Gouden Bol.' : 'De landelijke finale van De Gouden Bol '.$year.': twaalf provinciewinnaars, één blinde finaletest, één landelijke lijst.'">
    <x-slot:head>
        @if ($positions->isNotEmpty())
            <x-ui.json-ld :data="[
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => 'Landelijke lijst – De Gouden Bol '.$year,
                'itemListElement' => $positions->take($listLength)->map(fn ($p) => ['@type' => 'ListItem', 'position' => $p->position, 'name' => $p->entry->public_name, 'url' => route('bakkers.toon', $p->entry->company)])->values()->all(),
            ]" />
        @endif
    </x-slot:head>

    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="watermerk pointer-events-none absolute top-1/2 -right-10 -translate-y-1/2 text-[clamp(8rem,16vw,14rem)] !opacity-[0.05]" aria-hidden="true">NL</div>
        <div class="site-container relative">
            <x-ui.eyebrow>Landelijke finale {{ $year }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">De beste oliebol<br>van <em>Nederland</em></h1>
            <p class="body-text max-w-[620px]">
                @if ($winner)
                    De beste oliebol van Nederland {{ $year }} komt van <strong>{{ $winner->entry->public_name }}</strong> uit {{ $winner->entry->province->name }}, met een {{ $winner->formattedTotal() }} van het finalepanel. De provinciewinnaars werden opnieuw blind beoordeeld; de provinciale scores telden niet mee.
                @elseif ($revealed)
                    De provinciewinnaars nemen het tegen elkaar op in een nieuwe, blinde finaletest{{ $edition?->final_test_day ? ' op '.DutchTime::date($edition->final_test_day) : '' }}. De landelijke uitslag volgt{{ $edition?->national_result_at ? ' op '.DutchTime::format($edition->national_result_at, 'D MMMM YYYY') : '' }}.
                @else
                    De provinciewinnaars worden bekend op {{ $edition?->main_publication_at ? DutchTime::format($edition->main_publication_at, 'D MMMM YYYY') : 'de publicatiedag' }}. Zij nemen het daarna tegen elkaar op in een nieuwe, blinde finaletest.
                @endif
            </p>
        </div>
    </section>

    @if ($positions->isNotEmpty())
        <section class="section--sub py-14">
            <div class="site-container">
                <x-ui.eyebrow>Landelijke lijst</x-ui.eyebrow>
                <h2 class="kop-2 mb-8">Top {{ $listLength }} van Nederland</h2>
                <ol class="flex flex-col gap-3" aria-label="Landelijke lijst {{ $year }}">
                    @foreach ($positions->take($listLength) as $position)
                        <li>
                            <a href="{{ route('bakkers.toon', $position->entry->company) }}" class="card card--hover flex items-center gap-5 !p-5 no-underline sm:!px-7">
                                <span class="font-kop w-12 shrink-0 text-center text-[2.2rem] leading-none font-bold text-espresso tabular-nums">{{ $position->position }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-kop text-kaarttitel font-semibold text-espresso">{{ $position->entry->public_name }}</span>
                                    <span class="block text-[0.78rem] text-gedempt">{{ $position->entry->company->primaryLocation?->city }} · {{ $position->entry->province->name }}</span>
                                </span>
                                <x-ui.rank-pill :score="$position->total" :top="$position->position === 1" />
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    <section class="bg-zand py-16">
        <div class="site-container">
            <x-ui.eyebrow>Finalisten</x-ui.eyebrow>
            <h2 class="kop-2 mb-8">{{ $finalists->count() ?: 'De' }} provinciewinnaars</h2>
            @if ($finalists->isEmpty())
                <x-ui.empty-state titel="De finalisten zijn nog niet bekend" tekst="Per provincie wordt de winnaar op de publicatiedag onthuld. Daarna staan alle finalisten hier." />
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($finalists as $finalist)
                        <a href="{{ route('bakkers.toon', $finalist->entry->company) }}" class="card card--hover block no-underline">
                            <div class="mb-1.5 text-[0.62rem] font-bold tracking-[0.2em] text-goud-tekst uppercase">{{ $finalist->province->name }} · {{ $finalist->originLabel() }}</div>
                            <div class="font-kop text-kaarttitel font-semibold text-espresso">{{ $finalist->entry->public_name }}</div>
                            <div class="text-[0.78rem] text-gedempt">&#9679; {{ $finalist->entry->company->primaryLocation?->city }}</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($sponsors->isNotEmpty())
        <section class="section--sub border-t border-rand-sterk py-14">
            <div class="site-container">
                <x-ui.eyebrow>Sponsoren van de finale</x-ui.eyebrow>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($sponsors as $placement)
                        <x-sponsor-tile :sponsor="$placement->sponsor" :label="$placement->label ?: $placement->locationLabel()" :compact="true" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
