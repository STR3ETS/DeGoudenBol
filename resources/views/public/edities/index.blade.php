<x-layouts.public title="Archief" description="Alle edities van De Gouden Bol met de definitieve Top 10 per provincie en de landelijke lijst.">
    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="site-container relative">
            <x-ui.eyebrow>Archief</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Alle <em>edities</em></h1>
            <p class="body-text max-w-[560px]">Uitslagen blijven vindbaar. Per editie de definitieve Top 10 van iedere provincie en de landelijke lijst.</p>
        </div>
    </section>
    <section class="bg-zand py-16">
        <div class="site-container">
            @if ($editions->isEmpty())
                <x-ui.empty-state titel="Nog geen afgeronde editie" tekst="Na de publicatiedag verschijnt hier de eerste editie." />
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($editions as $edition)
                        <a href="{{ route('edities.show', $edition) }}" class="card card--hover block no-underline">
                            <div class="font-kop text-[2.4rem] leading-none font-bold text-espresso">{{ $edition->year }}</div>
                            <div class="mt-2 text-[0.85rem] text-gedempt">{{ $edition->name }} · {{ $edition->status->getLabel() }}</div>
                            <div class="mt-4 text-[0.78rem] font-bold text-goud-tekst">Bekijk uitslagen &rarr;</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
