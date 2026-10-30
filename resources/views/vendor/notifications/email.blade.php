{{-- Alle notificatiemails in de huisstijl. Extra blokken komen uit App\Support\Mail\GoldenMail. --}}
<x-mail::message
    :hero-title="$heroTitle ?? null"
    :hero-accent="$heroAccent ?? null"
    :preheader="$preheader ?? ($introLines[0] ?? null)"
    :recipient="$recipient ?? null"
    :signoff="$salutation ?? null"
>
{{-- Aanhef --}}
@if (! empty($greeting))
# {{ $greeting }}
@endif

{{-- Inleiding --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Kaart met de kerngegevens --}}
@if (! empty($panel))
<x-mail::panel :eyebrow="$panel['eyebrow'] ?? null" :title="$panel['title'] ?? null">
{!! collect($panel['lines'] ?? [])->map(fn ($line) => e($line))->implode('<br>') !!}
@if (! empty($panel['footnote']))
<br><br>{{ $panel['footnote'] }}
@endif
</x-mail::panel>
@endif

{{-- Knop --}}
@isset($actionText)
<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Afsluiting --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Link als de knop niet werkt --}}
@isset($actionText)
<x-slot:subcopy>
Werkt de knop niet? Kopieer dan deze link in uw browser: <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset

{{-- Beige blok onderaan --}}
@if (! empty($note))
<x-slot:note>
<x-mail::note :title="$note['title'] ?? null">
{{ $note['text'] ?? '' }}
</x-mail::note>
</x-slot:note>
@endif
</x-mail::message>
