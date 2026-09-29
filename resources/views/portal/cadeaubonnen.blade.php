@php
    use App\Domain\Vouchers\Enums\CampaignStatus;
    use App\Support\DutchTime;
    use App\Support\Money;
@endphp
<x-layouts.portal title="Cadeaubonnen">
    <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-3">Cadeaubonnen<em>actie</em></h1>

    @if ($campaign === null)
        <p class="body-text mb-8 max-w-[640px]">De cadeaubonnenactie hoort bij een plek in de definitieve Top 10. Staat uw bedrijf daarin, dan verschijnt de actie hier na de publicatie.</p>
        <x-ui.empty-state titel="Geen actie voor dit bedrijf" />
    @else
        <p class="body-text mb-8 max-w-[640px]">U geeft {{ $campaign->winner_count }} cadeaubonnen van {{ Money::format($campaign->voucher_value_cents) }} weg. Kies de winnaars op uw eigen social media en voer ze hieronder in; het platform mailt de claimlink en geeft de bon uit. Verzilveren doet u met de <a href="{{ route('portaal.scan', $company) }}" class="font-bold text-goud-tekst underline">scanner</a>.</p>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat :cijfer="$report['winners'].' / '.$campaign->winner_count" label="Winnaars ingevoerd" />
            <x-ui.stat :cijfer="(string) $report['issued']" label="Bonnen uitgegeven (geclaimd)" />
            <x-ui.stat :cijfer="(string) $report['redeemed']" label="Verzilverd" />
            <x-ui.stat :cijfer="(string) $report['expired']" label="Verlopen" />
        </div>

        <x-ui.card class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <x-ui.chip :status="$campaign->status === CampaignStatus::Open ? 'succes' : 'neutraal'" :dot="true">{{ $campaign->status->getLabel() }}</x-ui.chip>
                    <p class="mt-2 text-[0.85rem] text-gedempt">Start {{ DutchTime::format($campaign->starts_at, 'D MMM, HH:mm') }} · winnaars invoeren tot {{ DutchTime::format($campaign->winners_deadline_at, 'dddd D MMMM, HH:mm') }} · verzilveren t/m {{ DutchTime::date($campaign->last_redeem_day) }}</p>
                </div>
                @if ($report['shortfall'] > 0)
                    <x-ui.chip status="waarschuwing">Tekort: {{ $report['shortfall'] }} bonnen aangevuld door Bonbeheer</x-ui.chip>
                @endif
            </div>
        </x-ui.card>

        @if ($canEdit && $campaign->acceptsWinners() && $campaign->remainingSlots() > 0)
            <x-ui.card class="mb-6">
                <h2 class="kop-3 mb-4 text-[1.2rem]">Winnaar toevoegen ({{ $campaign->remainingSlots() }} te gaan)</h2>
                <form method="post" action="{{ route('portaal.cadeaubonnen.winnaar', $company) }}" class="grid gap-4 sm:grid-cols-[1fr_120px_1.4fr_auto] sm:items-end">
                    @csrf
                    <x-ui.field name="first_name" label="Voornaam" :required="true" />
                    <x-ui.field name="last_initial" label="Letter achternaam" :required="true" maxlength="4" />
                    <x-ui.field name="email" label="E-mailadres" type="email" :required="true" />
                    <x-ui.button type="submit">Toevoegen</x-ui.button>
                </form>
                <p class="mt-3 text-[0.78rem] text-gedempt">Per e-mailadres maximaal {{ $campaign->edition->settings->voucherMaxPerEmail }} bon per editie. De winnaar accepteert zelf de actievoorwaarden en geeft eventueel toestemming voor naam en foto.</p>
            </x-ui.card>
        @endif

        <x-ui.card class="!p-0 overflow-hidden">
            <table class="w-full text-[0.88rem]">
                <thead class="bg-zand text-left text-[0.68rem] font-bold tracking-[0.12em] text-gedempt uppercase">
                    <tr><th class="px-5 py-3">Winnaar</th><th class="px-5 py-3">E-mail</th><th class="px-5 py-3">Geclaimd</th><th class="px-5 py-3">Bon</th><th class="px-5 py-3">Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($winners as $winner)
                        <tr class="border-t border-rand">
                            <td class="px-5 py-3 font-semibold">{{ $winner->displayName() }}</td>
                            <td class="px-5 py-3 text-gedempt">{{ $winner->anonymized_at ? '–' : $winner->email }}</td>
                            <td class="px-5 py-3">{{ $winner->claimed_at ? DutchTime::format($winner->claimed_at, 'D MMM, HH:mm') : 'nog niet' }}</td>
                            <td class="px-5 py-3 font-mono">{{ $winner->voucher?->code ?? '–' }}</td>
                            <td class="px-5 py-3">@if ($winner->voucher)<x-ui.chip :status="$winner->voucher->effectiveStatus()->chipStatus()">{{ $winner->voucher->effectiveStatus()->getLabel() }}</x-ui.chip>@else<x-ui.chip status="neutraal">Wacht op claim</x-ui.chip>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-6 text-center text-gedempt">Nog geen winnaars ingevoerd.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.card>
    @endif
</x-layouts.portal>
