@php
    use App\Domain\Participants\Enums\DiscountStatus;
    use App\Support\DutchTime;

    $paid = $order->isPaid();
    $pending = $payment !== null && ! $payment->status->isFinal() && ! $paid;
    $firstPayment = ! $paid && ! $pending && $payment === null && $canRetry;
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
                <p class="body-text mb-4">{{ $entry->public_name }} doet mee in de provincie {{ $entry->province->name }}. We hebben een bevestiging met de factuur en een inloglink voor het deelnemersportaal gestuurd naar het opgegeven e-mailadres.</p>
                <p class="body-text mb-8 text-[0.9rem]">In het portaal kiest u hoe uw oliebollen bij ons komen: zelf brengen op een tijdslot of op vrijdag laten ophalen.</p>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('portaal.inloggen')">Naar het portaal</x-ui.button>
                    <x-ui.button variant="outline" :href="route('home')">Naar de homepage</x-ui.button>
                </div>
            @elseif ($pending)
                <x-ui.chip status="info" :dot="true" class="mb-4">Betaling wordt verwerkt</x-ui.chip>
                <h1 class="kop-pagina mb-5">Een <em>ogenblik</em></h1>
                <p class="body-text mb-8">We wachten op de bevestiging van de betaling. Deze pagina ververst automatisch.</p>
            @elseif ($discountPending)
                <x-ui.chip status="info" :dot="true" class="mb-4">{{ $entry->discount_status->getLabel() }}</x-ui.chip>
                <h1 class="kop-pagina mb-5">Bedankt, uw aanmelding is <em>binnen</em></h1>
                <p class="body-text mb-4">U koos voor de kortingsactie. Uw plek in {{ $entry->province->name }} blijft gereserveerd tot {{ DutchTime::format($entry->reservation_expires_at, 'D MMMM YYYY') }}.</p>
                <p class="body-text mb-8 text-[0.9rem]">
                    @if ($entry->discount_status === DiscountStatus::Submitted)
                        We bekijken uw post. Daarna ontvangt u een mail met de betaallink voor de actieprijs.
                    @else
                        We hebben een mail gestuurd met een inloglink. Plaats het filmpje, tag De Gouden Bol en zet de link naar uw post in het portaal.
                    @endif
                </p>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('portaal.inloggen')">Naar het portaal</x-ui.button>
                    <form method="post" action="{{ route('aanmelden.standaardprijs', $order) }}">
                        @csrf
                        <x-ui.button type="submit" variant="outline">Toch direct betalen tegen de standaardprijs</x-ui.button>
                    </form>
                </div>
            @elseif ($firstPayment)
                <x-ui.chip status="succes" :dot="true" class="mb-4">
                    {{ $entry->discount_status === DiscountStatus::Approved ? 'Korting goedgekeurd' : 'Klaar om te betalen' }}
                </x-ui.chip>
                <h1 class="kop-pagina mb-5">Rond uw aanmelding <em>af</em></h1>
                <p class="body-text mb-8">Uw plek in {{ $entry->province->name }} is gereserveerd tot {{ DutchTime::format($entry->reservation_expires_at, 'D MMMM YYYY, HH:mm') }}. Na de betaling ontvangt u de bevestiging en de factuur.</p>
                <form method="post" action="{{ route('aanmelden.opnieuw', $order) }}" class="flex flex-wrap gap-3">
                    @csrf
                    <x-ui.button type="submit">Betalen · {{ $order->formattedTotal() }}</x-ui.button>
                </form>
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
