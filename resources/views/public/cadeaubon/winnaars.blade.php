<x-layouts.public title="Winnaars cadeaubonnen" description="De winnaars van de cadeaubonnenactie van de Top 10-ondernemers van De Gouden Bol.">
    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="site-container">
            <x-ui.eyebrow>Cadeaubonnenactie</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">De <em>winnaars</em></h1>
            <p class="body-text max-w-[600px]">Iedere Top 10-ondernemer geeft cadeaubonnen weg aan klanten. Hier staan de winnaars die daar toestemming voor gaven, met voornaam en eerste letter.</p>
        </div>
    </section>
    <section class="bg-zand py-16">
        <div class="site-container">
            @if ($campaigns->isEmpty())
                <x-ui.empty-state titel="Nog geen acties gestart" tekst="De cadeaubonnenactie start na de publicatie van de definitieve Top 10." />
            @else
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($campaigns as $campaign)
                        <div class="card">
                            <div class="mb-1.5 text-[0.62rem] font-bold tracking-[0.2em] text-goud-tekst uppercase">{{ $campaign->province->name }}</div>
                            <h2 class="font-kop text-kaarttitel font-semibold text-espresso"><a href="{{ route('bakkers.toon', $campaign->company) }}" class="no-underline">{{ $campaign->company->name }}</a></h2>
                            @if ($campaign->winners->isEmpty())
                                <p class="mt-3 text-[0.85rem] text-gedempt">Winnaars volgen.</p>
                            @else
                                <ul class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($campaign->winners as $winner)
                                        <li><x-ui.chip status="goud">{{ $winner->displayName() }}</x-ui.chip></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
