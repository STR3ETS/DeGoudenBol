@php
    $principes = [
        ['Eén ding tegelijk', 'bliksem', 'Elk scherm begint met de belangrijkste actie van dat moment, groot en bovenaan. De rest staat eronder, kleiner. Wie alleen het eerste blok leest, weet genoeg.', 'Vandaag-kaart met één grote knop', 'Zes even grote tegels zonder volgorde'],
        ['Gewone taal', 'document', 'Geen jargon, geen statuscodes. "Je plek is betaald" in plaats van "Order: paid". Cijfers krijgen altijd een zin erbij die zegt wat je ermee moet.', '"7 bakkers wachten op betaling. Je hoeft niets te doen."', '"Pending payment: 7"'],
        ['Uitleg op de plek zelf', 'vraag', 'Hulp staat naast het veld of de knop, niet in een handleiding ver weg. Een "Waarom?" klapt open, een tipje staat in de kaart, een hulpkaart sluit elke pagina af.', 'Waarom-details onder de hoofdactie', 'Alleen een link naar een pdf-handleiding'],
        ['Je kunt niets kapotmaken', 'vergrendel', 'Onomkeerbare dingen vragen om bevestiging in een modal met de gevolgen in één zin. Alles wat al klaar is, is groen en gesloten. Wat nog niet kan, is grijs met de reden erbij.', 'Grijze actie: "Komt beschikbaar zodra je een moment hebt gekozen."', 'Knop die een foutmelding geeft na klikken'],
        ['Groot en rustig', 'zon', 'Tikdoelen zijn minimaal 48 pixels, formulieren staan in één kolom van maximaal 640 pixels, labels zijn normale zinnen. Witruimte is geen verspilling maar leesbaarheid.', 'Rustig formulier met vier velden onder elkaar', 'Drie kolommen met twaalf velden'],
        ['Vier de mijlpalen', 'feest', 'De 300e bakker, een betaalde plek, een cijfer: dat mag feestelijk. De mascotte en de feestkaart zijn er voor die momenten, nooit voor foutmeldingen.', 'Feestkaart met de bol en een gouden gloed', 'Confetti bij elke opgeslagen wijziging'],
    ];
@endphp
<section id="principes" class="mb-20 scroll-mt-28">
    <h2 class="kop-2 mb-3">Zes principes</h2>
    <p class="body-text mb-8 max-w-[720px]">Dit is de maat voor elk scherm, in de backoffice én in het portaal. Twijfel je over een ontwerpkeuze, loop deze zes langs. Alle nieuwe componenten hieronder bestaan omdat een principe erom vroeg.</p>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($principes as $i => [$titel, $icoon, $tekst, $goed, $fout])
            <div class="ui-principe">
                <span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon :naam="$icoon" /></span>
                <span class="ui-principe__nr">Principe {{ $i + 1 }}</span>
                <h3 class="ui-principe__titel">{{ $titel }}</h3>
                <p class="ui-principe__tekst">{{ $tekst }}</p>
                <div class="ui-principe__doe">
                    <span class="is-goed"><x-ui.icoon naam="vink" dikte="2.6" /> {{ $goed }}</span>
                    <span class="is-fout"><x-ui.icoon naam="kruis" dikte="2.6" /> {{ $fout }}</span>
                </div>
            </div>
        @endforeach
    </div>
</section>
