<x-layouts.public :title="'Uitslagen '.$edition->year" :description="'De definitieve Top 10 per provincie en de landelijke lijst van De Gouden Bol '.$edition->year.'.'">
    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="watermerk pointer-events-none absolute top-1/2 -right-10 -translate-y-1/2 text-[clamp(8rem,16vw,14rem)] !opacity-[0.05]" aria-hidden="true">{{ $edition->year }}</div>
        <div class="site-container relative">
            <nav class="mb-5 text-[0.7rem] font-bold tracking-[0.14em] text-gedempt uppercase" aria-label="Kruimelpad">
                <a href="{{ route('edities.index') }}" class="hover:text-goud-tekst">&larr; Alle edities</a>
            </nav>
            <x-ui.eyebrow>{{ $edition->name }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Uitslagen <em>{{ $edition->year }}</em></h1>
        </div>
    </section>

    @if ($national->isNotEmpty())
        <section class="section--sub py-14">
            <div class="site-container">
                <x-ui.eyebrow>Landelijke lijst</x-ui.eyebrow>
                <h2 class="kop-2 mb-6">Nederland</h2>
                <ol class="flex flex-col gap-3">
                    @foreach ($national as $position)
                        <li><a href="{{ route('bakkers.toon', $position->entry->company) }}" class="card card--hover flex items-center gap-5 !p-4 no-underline">
                            <span class="font-kop w-10 text-center text-[1.8rem] leading-none font-bold text-espresso tabular-nums">{{ $position->position }}</span>
                            <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-espresso">{{ $position->entry->public_name }}</span><span class="block text-[0.75rem] text-gedempt">{{ $position->entry->province->name }}</span></span>
                            <x-ui.rank-pill :score="$position->total" :top="$position->position === 1" />
                        </a></li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    <section class="bg-zand py-16">
        <div class="site-container">
            <x-ui.eyebrow>Per provincie</x-ui.eyebrow>
            <h2 class="kop-2 mb-8">Definitieve Top {{ $edition->settings->provincialListLength }}</h2>
            @if ($lists === [])
                <x-ui.empty-state titel="Nog geen definitieve lijsten" tekst="De lijsten verschijnen per provincie op de publicatiedag." />
            @else
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($lists as $list)
                        <div class="card">
                            <h3 class="kop-3 mb-4">{{ $list['province']->name }}</h3>
                            <ol class="flex flex-col gap-2 text-[0.9rem]">
                                @forelse ($list['positions'] as $position)
                                    <li class="flex items-center justify-between gap-3 border-b border-rand pb-2 last:border-0">
                                        <span class="min-w-0 truncate"><span class="font-bold text-gedempt">{{ $position->position }}.</span> <a href="{{ route('bakkers.toon', $position->entry->company) }}" class="font-semibold text-espresso">{{ $position->entry->public_name }}</a></span>
                                        <span class="font-bold text-goud-tekst tabular-nums">{{ $position->formattedTotal() }}</span>
                                    </li>
                                @empty
                                    <li class="text-gedempt">Geen publiceerbaar resultaat.</li>
                                @endforelse
                            </ol>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
