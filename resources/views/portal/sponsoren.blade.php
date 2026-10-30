@php use App\Support\DutchTime; @endphp
<x-layouts.portal title="Sponsoren">
    <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-3">Bakt <em>met</em></h1>
    <p class="body-text mb-8 max-w-[640px]">Een sponsor kan vragen om "Bakt met [sponsor]" op uw openbare profiel. U beslist zelf; zonder uw bevestiging verschijnt er niets. Sponsoring heeft nooit invloed op de keuring of de uitslag.</p>

    @if ($links->isEmpty())
        <x-ui.empty-state titel="Geen sponsorkoppelingen" tekst="Zodra een sponsor een koppeling aanvraagt, staat die hier." />
    @else
        <div class="flex flex-col gap-4">
            @foreach ($links as $link)
                <x-ui.card class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <x-sponsor-tile :sponsor="$link->sponsor" :compact="true" class="!border-0 !p-0 !shadow-none" />
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($link->isConfirmed())
                            <x-ui.chip status="succes" :dot="true">Bevestigd {{ DutchTime::date($link->confirmed_by_company_at) }}</x-ui.chip>
                        @elseif ($link->declined_at)
                            <x-ui.chip status="neutraal">Geweigerd</x-ui.chip>
                        @else
                            <x-ui.chip status="waarschuwing" :dot="true">Wacht op uw beslissing</x-ui.chip>
                        @endif
                        @if ($canDecide && ! $link->isConfirmed())
                            <form method="post" action="{{ route('portaal.sponsoren.reageren', [$company, $link]) }}">@csrf<input type="hidden" name="beslissing" value="bevestigen"><x-ui.button type="submit" size="sm">Bevestigen</x-ui.button></form>
                        @endif
                        @if ($canDecide && ! $link->declined_at)
                            <form method="post" action="{{ route('portaal.sponsoren.reageren', [$company, $link]) }}">@csrf<input type="hidden" name="beslissing" value="weigeren"><x-ui.button type="submit" variant="outline" size="sm">Weigeren</x-ui.button></form>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    @if ($offers->isNotEmpty())
        <h2 class="kop-3 mt-12 mb-2">Aanbiedingen van sponsoren</h2>
        <p class="body-text mb-6 max-w-[640px] text-[0.9rem]">Door de redactie goedgekeurd. Interesse? Neem contact op met de sponsor; De Gouden Bol is geen partij in de aanbieding.</p>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($offers as $offer)
                <x-ui.card>
                    <x-ui.eyebrow>{{ $offer->type->getLabel() }} · {{ $offer->sponsor->name }}</x-ui.eyebrow>
                    <h3 class="kop-3 text-[1.15rem]">{{ $offer->title }}</h3>
                    <p class="mt-2 text-[0.9rem] whitespace-pre-line text-gedempt">{{ $offer->description }}</p>
                    @if ($offer->sponsor->url)
                        <a href="{{ $offer->sponsor->url }}" class="mt-3 inline-block text-[0.85rem] font-bold text-goud-tekst underline" target="_blank" rel="noopener sponsored">Naar {{ $offer->sponsor->name }}</a>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    @endif
</x-layouts.portal>
