@php
    $paid = $order?->isPaid() ?? false;
    $pending = $order && $payment && ! $payment->status->isFinal() && ! $paid;
@endphp
<x-layouts.site title="Sponsoring" :noindex="true" main-class="page-sec">
@if ($pending)
    <meta http-equiv="refresh" content="5">
@endif
<section class="page-hero" aria-labelledby="h1">
    <div class="page-hero-inner">
        <span class="eyebrow">Sponsoring</span>
        @if ($order === null)
            <h1 class="h1" id="h1">Bedankt, uw aanvraag is <em>binnen</em></h1>
            <p class="lead">
                @if ($sponsor->requestedProduct?->code->value === 'national_top')
                    Uw reservering als Top 10 partner is ontvangen. Na de officiële uitslag nemen wij contact op: u kiest een deelnemer, die geeft akkoord en pas dan rekent u af. Een plaats in de ranking wordt nooit gesuggereerd of gegarandeerd.
                @else
                    Wij nemen contact met u op om de invulling en de prijs van uw maatwerkpakket af te stemmen. U zit nergens aan vast.
                @endif
            </p>
        @elseif ($paid)
            <h1 class="h1" id="h1">Bedankt voor uw <em>sponsoring</em></h1>
            <p class="lead">De betaling is ontvangen. U krijgt de bevestiging, de factuur en uw persoonlijke beheerlink per mail. Wij controleren uw logo en gegevens en zetten uw vermelding daarna online.</p>
        @elseif ($pending)
            <h1 class="h1" id="h1">Een <em>ogenblik</em></h1>
            <p class="lead">We wachten op de bevestiging van de betaling. Deze pagina ververst automatisch.</p>
        @else
            <h1 class="h1" id="h1">De betaling is <em>niet gelukt</em></h1>
            <p class="lead">Uw aanmelding staat klaar. Via de beheerlink in uw mail of hieronder kunt u alsnog betalen.</p>
            <div class="btn-row" style="justify-content:flex-start;margin-top:24px"><a href="{{ route('sponsoring.beheer') }}" class="btn-pill"><span>Beheerlink aanvragen</span></a></div>
        @endif
        @if ($order === null || $paid)
            <div class="btn-row" style="justify-content:flex-start;margin-top:28px"><a href="{{ route('sponsoren') }}" class="btn-pill"><span>Terug naar sponsoring</span></a></div>
        @endif
    </div>
</section>
</x-layouts.site>
