@php
    use App\Domain\Commerce\Enums\PaymentStatus;

    $paid = $order->isPaid();
    $pending = $payment !== null && ! $payment->status->isFinal() && ! $paid;
@endphp
<x-layouts.public title="Status van uw aanmelding">
    @if ($pending)
        <x-slot:head>
            <meta http-equiv="refresh" content="5">
        </x-slot:head>
    @endif

    <div class="site-container pt-36 pb-24">
        <div class="mx-auto max-w-[640px]">
            <x-ui.eyebrow>Aanmelding {{ $order->number }}</x-ui.eyebrow>

            @if ($paid)
                <x-ui.chip status="succes" :dot="true" class="mb-4">Betaling ontvangen</x-ui.chip>
                <h1 class="kop-pagina mb-5">Welkom bij <em>De Gouden Bol {{ $order->edition->year }}</em></h1>
                <p class="body-text mb-4">{{ $entry->public_name }} doet mee in de provincie {{ $entry->province->name }}. We hebben een bevestiging met een inloglink voor het deelnemersportaal gestuurd naar het opgegeven e-mailadres.</p>
                <p class="body-text mb-8 text-[0.9rem]">In het portaal beheert u uw profiel, kiest u straks een aanleverslot en vindt u uw factuur.</p>
                <div class="flex flex-wrap gap-3">
                    @if (Route::has('portaal.inloggen'))
                        <x-ui.button :href="route('portaal.inloggen')">Naar het portaal</x-ui.button>
                    @endif
                    <x-ui.button variant="outline" :href="route('home')">Naar de homepage</x-ui.button>
                </div>
            @elseif ($pending)
                <x-ui.chip status="info" :dot="true" class="mb-4">Betaling wordt verwerkt</x-ui.chip>
                <h1 class="kop-pagina mb-5">Een <em>ogenblik</em></h1>
                <p class="body-text mb-8">We wachten op de bevestiging van de betaling. Deze pagina ververst automatisch.</p>
            @else
                <x-ui.chip status="waarschuwing" :dot="true" class="mb-4">{{ $payment?->status->getLabel() ?? 'Nog niet betaald' }}</x-ui.chip>
                <h1 class="kop-pagina mb-5">De betaling is <em>niet gelukt</em></h1>
                @if ($canRetry)
                    <p class="body-text mb-8">Uw plek in {{ $entry->province->name }} blijft nog even gereserveerd. U kunt de betaling opnieuw proberen.</p>
                    <form method="post" action="{{ route('aanmelden.opnieuw', $order) }}" class="flex flex-wrap gap-3">
                        @csrf
                        <x-ui.button type="submit">Opnieuw betalen · {{ $order->formattedTotal() }}</x-ui.button>
                        <x-ui.button variant="outline" :href="route('home')">Later</x-ui.button>
                    </form>
                @else
                    <p class="body-text mb-8">De reservering van uw plek is verlopen. Meld u opnieuw aan; als er nog plekken zijn in uw provincie kunt u direct verder.</p>
                    <x-ui.button :href="route('aanmelden')">Opnieuw aanmelden</x-ui.button>
                @endif
            @endif
        </div>
    </div>
</x-layouts.public>
