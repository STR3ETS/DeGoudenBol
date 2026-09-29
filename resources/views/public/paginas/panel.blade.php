@php $year = $edition?->year ?? now()->year; @endphp
<x-layouts.public title="Het panel" description="Het onafhankelijke panel van De Gouden Bol proeft blind: alleen testnummers, vergrendelde scorekaarten en vier ogen op iedere publicatie.">
    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="site-container max-w-[820px]">
            <x-ui.eyebrow>Het panel</x-ui.eyebrow>
            <h1 class="kop-pagina mb-5">Onafhankelijk en <em>blind</em></h1>
            <p class="body-text text-[1.05rem]">Het panel proeft zonder te weten wie er bakt. Zo blijft de smaak het enige dat telt. Panelleden werken volgens een vast rooster uit een pool en ontvangen een vaste vergoeding per sessie.</p>
        </div>
    </section>

    <section class="bg-zand py-16">
        <div class="site-container grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Alleen testnummers', 'Het panel ziet nooit een naam, logo of verpakking. De koppeling naar de bakker staat in een afgeschermde kluis; iedere inzage wordt gelogd.'],
                ['Minimaal '.($settings?->minValidCards ?? 6).' proevers per bol', 'Een bol krijgt pas een cijfer als genoeg panelleden hem onafhankelijk hebben gescoord. Kaarten die sterk afwijken van de rest gaan naar scorecontrole.'],
                ['Vergrendelde scorekaart', 'Na indienen kan een kaart niet meer worden aangepast. Panelleden zien hun eerdere scores niet terug, zodat ze blind blijven voor de finale.'],
                ['Belangenconflict gemeld', 'Wie een deelnemer kent, meldt dat vooraf en proeft dat monster niet. Het uitserveerschema regelt dat zonder namen te tonen.'],
            ] as $index => [$title, $text])
                <x-ui.card>
                    <div class="icoonvak mb-4" aria-hidden="true">&#9733;</div>
                    <h2 class="mb-2 font-kop text-[1.15rem] font-semibold">{{ $title }}</h2>
                    <p class="body-text text-[0.85rem]">{{ $text }}</p>
                </x-ui.card>
            @endforeach
        </div>
    </section>

    <section class="py-16">
        <div class="site-container max-w-[820px]">
            <x-ui.eyebrow>Wie zit erin?</x-ui.eyebrow>
            <h2 class="kop-2 mb-5">Namen volgen <em>na de editie</em></h2>
            <p class="body-text">
                @if ($settings?->showPanelAfterEdition ?? true)
                    Om beïnvloeding tijdens het seizoen uit te sluiten maken we de samenstelling van het panel pas na afloop van editie {{ $year }} bekend.
                @else
                    De samenstelling van het panel van editie {{ $year }} wordt hier gepubliceerd.
                @endif
            </p>
        </div>
    </section>
</x-layouts.public>
