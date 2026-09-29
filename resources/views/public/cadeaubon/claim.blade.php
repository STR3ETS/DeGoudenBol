@php use App\Support\DutchTime; @endphp
<x-layouts.public title="Cadeaubon claimen" description="Bevestig je cadeaubon van De Gouden Bol.">
    <x-slot:head><meta name="robots" content="noindex, nofollow"></x-slot:head>
    <section class="relative overflow-hidden pt-36 pb-16">
        <div class="site-container max-w-[680px]">
            <x-ui.eyebrow>Cadeaubon van {{ $campaign->company->name }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Gefeliciteerd, <em>{{ $winner->first_name }}</em></h1>

            @if ($alreadyClaimed)
                <x-ui.card><p class="body-text">Deze bon is al geclaimd. De link naar je bon staat in de e-mail die je daarna hebt ontvangen.</p></x-ui.card>
            @elseif ($expired)
                <x-ui.card><p class="body-text">De actie is afgelopen; deze bon kan niet meer worden geclaimd.</p></x-ui.card>
            @else
                <p class="body-text mb-8">{{ $campaign->company->name }} staat in de Top 10 van De Gouden Bol {{ $campaign->edition->year }} en geeft je een cadeaubon van <strong>{{ \App\Support\Money::format($campaign->voucher_value_cents) }}</strong>, te verzilveren t/m {{ DutchTime::date($campaign->last_redeem_day) }}. Bevestig hieronder; daarna ontvang je de digitale bon met QR-code.</p>

                <x-ui.card>
                    <form method="post" action="{{ route('cadeaubon.claim.bevestigen', $token) }}" class="flex flex-col gap-4">
                        @csrf
                        <input type="hidden" name="terms_version_id" value="{{ $terms?->getKey() }}">
                        <label class="flex items-start gap-3 text-[0.9rem]">
                            <input type="checkbox" name="terms" value="1" class="mt-1 h-5 w-5 accent-goud" required>
                            <span>Ik accepteer de <a href="{{ route('actievoorwaarden') }}" target="_blank" rel="noopener" class="text-goud-tekst underline">actievoorwaarden</a>{{ $terms ? ' (versie '.$terms->version.')' : '' }} van de cadeaubonnenactie.</span>
                        </label>
                        @error('terms')<p class="field-error">{{ $message }}</p>@enderror
                        <label class="flex items-start gap-3 text-[0.9rem]">
                            <input type="checkbox" name="consent_public_name" value="1" class="mt-1 h-5 w-5 accent-goud">
                            <span>Mijn voornaam en eerste letter van mijn achternaam mogen op de winnaarspagina van De Gouden Bol staan.</span>
                        </label>
                        <label class="flex items-start gap-3 text-[0.9rem]">
                            <input type="checkbox" name="consent_photo" value="1" class="mt-1 h-5 w-5 accent-goud">
                            <span>Bij het verzilveren mag een foto worden gemaakt en gedeeld door {{ $campaign->company->name }} en De Gouden Bol.</span>
                        </label>
                        <div><x-ui.button type="submit">Cadeaubon claimen</x-ui.button></div>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </section>
</x-layouts.public>
