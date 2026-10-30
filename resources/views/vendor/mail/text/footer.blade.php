@props(['recipient' => null])
{{ config('app.name') }} · {{ config('commerce.issuer.legal_name') }} · {{ config('app.url') }}
@if (filled($recipient))
Deze e-mail is verzonden naar {{ $recipient }}.
@else
{{ $slot }}
@endif
