@props(['heroTitle' => null, 'heroAccent' => null, 'preheader' => null, 'recipient' => null, 'signoff' => null])
<x-mail::layout :preheader="$preheader">
{{-- Kop: ster, woordmerk en (optioneel) de titel van deze mail --}}
<x-slot:header>
<x-mail::header :url="config('app.url')" :title="$heroTitle" :accent="$heroAccent" />
</x-slot:header>

{{-- Inhoud --}}
{!! $slot !!}

{{-- Kleine lettertjes onder de knop --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Beige blok onderaan, bijvoorbeeld "En de landelijke uitslag?" --}}
@isset($note)
<x-slot:note>
{!! $note !!}
</x-slot:note>
@endisset

<x-slot:signoff>
@if (filled($signoff))
{!! nl2br(e($signoff)) !!}
@else
Met vriendelijke groet,<br><strong style="color:#27190c;">{{ config('mail.brand.team') }}</strong>
@endif
</x-slot:signoff>

<x-slot:footer>
<x-mail::footer :recipient="$recipient">
U ontvangt deze e-mail naar aanleiding van uw deelname aan of betrokkenheid bij {{ config('app.name') }}.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
