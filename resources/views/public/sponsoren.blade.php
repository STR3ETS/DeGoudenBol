<x-layouts.public title="Sponsoren en partners" description="Partners en sponsoren van De Gouden Bol, en de mogelijkheden om te sponsoren.">
    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="site-container">
            <x-ui.eyebrow>Sponsoren &amp; partners</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Mede mogelijk <em>gemaakt door</em></h1>
            <p class="body-text max-w-[620px]">Sponsoren maken de keuring mogelijk, maar hebben nooit invloed op de uitslag: het panel proeft blind en sponsoring bestaat niet in de keuring. Van iedere sponsorbijdrage gaat {{ $edition?->settings->charityPercentage ?? 10 }}% naar een goed doel.</p>
        </div>
    </section>

    <section class="bg-zand py-16">
        <div class="site-container flex flex-col gap-12">
            @if ($national->isNotEmpty())
                <div>
                    <x-ui.eyebrow>Landelijke partners</x-ui.eyebrow>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($national as $placement)
                            <x-sponsor-tile :sponsor="$placement->sponsor" :label="$placement->label ?: 'Landelijk partner'" />
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($provincePartners->isNotEmpty())
                <div>
                    <x-ui.eyebrow>Provinciepartners</x-ui.eyebrow>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($provincePartners as $placement)
                            <x-sponsor-tile :sponsor="$placement->sponsor" :label="'Provinciepartner '.$placement->province?->name" />
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($nationalTop->isNotEmpty() || $finale->isNotEmpty())
                <div>
                    <x-ui.eyebrow>Finale en landelijke toppositie</x-ui.eyebrow>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($nationalTop->merge($finale) as $placement)
                            <x-sponsor-tile :sponsor="$placement->sponsor" :label="$placement->locationLabel()" />
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($homepage->isNotEmpty())
                <div>
                    <x-ui.eyebrow>Sponsoren</x-ui.eyebrow>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($homepage as $placement)
                            <x-sponsor-tile :sponsor="$placement->sponsor" :compact="true" />
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($national->isEmpty() && $provincePartners->isEmpty() && $nationalTop->isEmpty() && $finale->isEmpty() && $homepage->isEmpty())
                <x-ui.empty-state titel="Nog geen sponsoren" tekst="De eerste partners en sponsoren van deze editie verschijnen hier." />
            @endif

            @if ($participantLinks > 0)
                <p class="text-[0.85rem] text-gedempt">Daarnaast tonen {{ $participantLinks }} deelnemersprofielen "Bakt met [sponsor]": een koppeling die de deelnemer zelf bevestigde.</p>
            @endif
        </div>
    </section>

    <section class="section" id="sponsorinformatie">
        <div class="site-container grid gap-10 lg:grid-cols-[1fr_1.2fr]">
            <div>
                <x-ui.eyebrow>Sponsorinformatie</x-ui.eyebrow>
                <h2 class="kop-2 mb-4">Zelf <em>sponsoren</em>?</h2>
                <p class="body-text mb-4">Vaste plaatsingen tegen vaste tarieven; partnerschappen op maat. Sponsoren zien nooit testgegevens en iedere plaatsing is gelabeld als sponsor. Interesse? Mail <a href="mailto:{{ config('press.contact_email') }}" class="font-bold text-goud-tekst">{{ config('press.contact_email') }}</a>.</p>
            </div>
            <div class="card !p-0 overflow-hidden">
                <table class="w-full table-fixed text-[0.88rem]">
                    <thead class="bg-zand text-left text-[0.68rem] font-bold tracking-[0.12em] text-gedempt uppercase"><tr><th class="px-5 py-3">Plaatsing</th><th class="w-[150px] px-5 py-3 text-right">Tarief excl. btw</th></tr></thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="border-t border-rand align-top">
                                <td class="px-5 py-3"><span class="font-semibold text-espresso">{{ $product->name }}</span>@if ($product->description)<span class="mt-0.5 block text-[0.78rem] leading-relaxed text-gedempt">{{ $product->description }}</span>@endif</td>
                                <td class="px-5 py-3 text-right">
                                    @if ($product->is_custom)
                                        Op maat
                                    @else
                                        <span class="font-semibold text-espresso">{{ $product->formattedPrice() }}</span>
                                        @if ($product->formattedExtraPrice())
                                            <span class="block text-[0.75rem] text-gedempt">extra: {{ $product->formattedExtraPrice() }}</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-5 py-6 text-center text-gedempt">De sponsorcatalogus volgt.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layouts.public>
