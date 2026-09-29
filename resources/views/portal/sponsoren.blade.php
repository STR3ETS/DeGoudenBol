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
</x-layouts.portal>
