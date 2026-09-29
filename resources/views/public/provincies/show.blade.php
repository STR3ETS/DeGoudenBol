@php
    use App\Domain\Ranking\Enums\SnapshotStatus;
    use App\Support\DutchTime;

    $year = $edition?->year ?? now()->year;
    $threshold = number_format($edition?->settings->publishThreshold ?? 5.0, 1, ',', '.');
    $leader = $top->first();
    $leaderEntry = $leader?->entry;
    $listTitle = $snapshot?->status === SnapshotStatus::Provisional ? 'Voorlopige Top '.$listLength : 'Definitieve Top '.$listLength;
    $publishedAt = $snapshot?->published_at;
@endphp
<x-layouts.public :title="'Voorlijst '.$province->name.' '.$year" :description="$leaderEntry ? 'De beste oliebol van '.$province->name.' '.$year.' komt op dit moment van '.$leaderEntry->public_name.' (cijfer '.$leader->formattedTotal().'), volgens de blinde keuring van De Gouden Bol.' : 'De beste oliebollen van '.$province->name.' volgens de blinde keuring van De Gouden Bol '.$year.'.'">
    <x-slot:head>
        <x-ui.json-ld :data="[
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Voorlijst', 'item' => route('provincies.index')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $province->name, 'item' => route('provincies.show', $province)],
            ],
        ]" />
        @if ($top->isNotEmpty())
            <x-ui.json-ld :data="[
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => $listTitle.' '.$province->name.' – De Gouden Bol '.$year,
                'itemListElement' => $top->map(fn ($p) => ['@type' => 'ListItem', 'position' => $p->position, 'name' => $p->entry->public_name, 'url' => route('bakkers.toon', $p->entry->company)])->values()->all(),
            ]" />
        @endif
    </x-slot:head>

    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="watermerk pointer-events-none absolute top-1/2 -right-10 -translate-y-1/2 text-[clamp(8rem,16vw,14rem)] !opacity-[0.05]" aria-hidden="true">{{ $province->code }}</div>
        <div class="site-container relative">
            <nav class="mb-5 text-[0.7rem] font-bold tracking-[0.14em] text-gedempt uppercase" aria-label="Kruimelpad">
                <a href="{{ route('provincies.index') }}" class="hover:text-goud-tekst">&larr; Alle provincies</a>
            </nav>
            <x-ui.eyebrow>Voorlijst {{ $year }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">De beste oliebollen<br>van <em>{{ $province->name }}</em></h1>
            @if ($pendingReveal)
                <div class="mb-5 inline-flex flex-wrap items-center gap-3 rounded-pil bg-espresso px-5 py-2.5 text-[0.85rem] font-bold text-room" data-reveal-at="{{ $pendingReveal->toIso8601String() }}">
                    <span class="h-2 w-2 rounded-full bg-goud" aria-hidden="true"></span>
                    Definitieve Top {{ $listLength }} en provinciewinnaar van {{ $province->name }}: {{ ucfirst(DutchTime::format($pendingReveal, 'dddd D MMMM, HH:mm')) }} uur
                </div>
            @endif
            <p class="body-text max-w-[620px]">
                @if ($leaderEntry)
                    De beste oliebol van {{ $province->name }} {{ $year }} komt op dit moment van <strong>{{ $leaderEntry->public_name }}</strong>{{ $leaderEntry->company->primaryLocation?->city ? ' in '.$leaderEntry->company->primaryLocation->city : '' }}, met een {{ $leader->formattedTotal() }} van het blinde panel. Ieder cijfer vanaf {{ $threshold }} is openbaar; de {{ strtolower($listTitle) }} wordt bijgewerkt na elke publicatie{{ $publishedAt ? ' (laatst '.DutchTime::format($publishedAt, 'D MMMM, HH:mm').')' : '' }}.
                @elseif ($edition?->first_test_day)
                    De Voorlijst van {{ $province->name }} verschijnt na de eerste testdag op {{ DutchTime::date($edition->first_test_day) }}. Ieder cijfer vanaf {{ $threshold }} komt openbaar; de definitieve Top {{ $listLength }} en de provinciewinnaar volgen op {{ DutchTime::format($edition->main_publication_at, 'D MMMM YYYY') }}.
                @else
                    De Voorlijst van {{ $province->name }} verschijnt zodra de eerste beoordelingen zijn gepubliceerd.
                @endif
            </p>
        </div>
    </section>

    @if ($top->isNotEmpty())
        <section class="section--sub py-14">
            <div class="site-container">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <x-ui.eyebrow>{{ $listTitle }}</x-ui.eyebrow>
                        <h2 class="kop-2">{{ $province->name }}</h2>
                    </div>
                    <p class="max-w-[420px] text-[0.85rem] text-gedempt">Blind beoordeeld op {{ $edition?->activeScoringModel?->criteria->count() ?? 8 }} onderdelen. Bij gelijke stand beslissen de onderdelen in vaste volgorde; een gedeelde plek op de grens krijgt een beslissende beoordeling.</p>
                </div>

                @if ($snapshot?->isFrozen() && $leaderEntry)
                    <div class="card card--espresso op-donker mb-6 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.eyebrow class="eyebrow--licht">Provinciewinnaar {{ $year }}</x-ui.eyebrow>
                            <div class="kop-3 !text-room">{{ $leaderEntry->public_name }}</div>
                            <div class="text-[0.85rem] text-room-zacht">{{ $leaderEntry->company->primaryLocation?->city }} · vertegenwoordigt {{ $province->name }} in de landelijke finale</div>
                        </div>
                        <x-ui.rank-pill :score="$leader->total" :top="true" />
                    </div>
                @endif

                <ol class="flex flex-col gap-3" aria-label="{{ $listTitle }} {{ $province->name }}">
                    @foreach ($top as $position)
                        @php $entry = $position->entry; $chip = $position->label->chipStatus(); @endphp
                        <li>
                            <a href="{{ route('bakkers.toon', $entry->company) }}" class="card card--hover flex items-center gap-5 !p-5 no-underline sm:!px-7">
                                <span class="font-kop w-12 shrink-0 text-center text-[2.2rem] leading-none font-bold text-espresso tabular-nums">{{ $position->position }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-kop text-kaarttitel font-semibold text-espresso">{{ $entry->public_name }}</span>
                                    <span class="block text-[0.78rem] text-gedempt">{{ $entry->company->primaryLocation?->city }}{{ $entry->company->type ? ' · '.$entry->company->type->getLabel() : '' }}</span>
                                </span>
                                @if ($position->needs_tie_break)
                                    <x-ui.chip status="waarschuwing" class="hidden sm:inline-flex">Beslissende beoordeling nodig</x-ui.chip>
                                @elseif ($chip)
                                    <x-ui.chip :status="$chip" class="hidden sm:inline-flex">{{ $position->label->getLabel() }}</x-ui.chip>
                                @endif
                                <x-ui.rank-pill :score="$position->total" :top="$position->position === 1" />
                            </a>
                        </li>
                    @endforeach
                </ol>

                @if ($rest->isNotEmpty())
                    <h3 class="kop-3 mt-12 mb-4">Ook officieel getest</h3>
                    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" aria-label="Overige beoordeelde deelnemers">
                        @foreach ($rest as $position)
                            <li>
                                <a href="{{ route('bakkers.toon', $position->entry->company) }}" class="card card--hover flex items-center justify-between gap-3 !p-4 no-underline">
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-espresso">{{ $position->position }}. {{ $position->entry->public_name }}</span>
                                        <span class="block text-[0.75rem] text-gedempt">{{ $position->entry->company->primaryLocation?->city }}</span>
                                    </span>
                                    <x-ui.rank-pill :score="$position->total" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    @if ($partner || $pressRelease)
        <section class="section--sub border-t border-rand-sterk py-10">
            <div class="site-container flex flex-wrap items-center justify-between gap-6">
                @if ($partner)
                    <x-sponsor-tile :sponsor="$partner->sponsor" :label="'Provinciepartner '.$province->name" class="min-w-[280px]" />
                @endif
                @if ($pressRelease)
                    <a href="{{ route('pers.toon', $pressRelease) }}" class="card card--hover flex items-center gap-4 !p-5 no-underline">
                        <span class="icoonvak" aria-hidden="true">&#9998;</span>
                        <span><span class="block text-[0.6rem] font-bold tracking-[0.2em] text-gedempt uppercase">Persbericht</span><span class="block font-semibold text-espresso">{{ $pressRelease->title }}</span></span>
                    </a>
                @endif
            </div>
        </section>
    @endif

    @if ($entries->isNotEmpty() || $top->isEmpty())
    <section class="bg-zand py-16">
        <div class="site-container">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <x-ui.eyebrow>{{ $top->isNotEmpty() ? 'Nog te beoordelen' : 'Deelnemers' }}</x-ui.eyebrow>
                    <h2 class="kop-2">{{ $entries->count() }} {{ $entries->count() === 1 ? 'deelnemer' : 'deelnemers' }}{{ $top->isNotEmpty() ? ' wachten op de uitslag' : ' in '.$province->name }}</h2>
                </div>
                @if ($availability)
                    <div class="text-[0.85rem] text-gedempt">
                        {{ $availability['available'] }} van {{ $availability['capacity'] }} plekken vrij
                        @if ($availability['available'] > 0 && Route::has('aanmelden'))
                            &middot; <a href="{{ route('aanmelden') }}" class="font-bold text-goud-tekst">Aanmelden</a>
                        @endif
                    </div>
                @endif
            </div>

            @if ($entries->isEmpty() && $top->isEmpty())
                <x-ui.empty-state titel="Nog geen deelnemers in {{ $province->name }}" tekst="Zodra de eerste bakker zich heeft aangemeld, staat die hier. Na de test verschijnt hier de Voorlijst.">
                    @if (Route::has('aanmelden') && ($availability['available'] ?? 0) > 0)
                        <x-ui.button :href="route('aanmelden')">Aanmelden als bakker</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @elseif ($entries->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($entries as $entry)
                        <x-bakker-kaart :entry="$entry" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif
</x-layouts.public>
