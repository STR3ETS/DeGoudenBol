@props(['heroTitle' => null, 'heroAccent' => null, 'preheader' => null, 'recipient' => null, 'signoff' => null])
<x-mail::layout>
    <x-slot:header>
        <x-mail::header :url="config('app.url')" :title="$heroTitle" :accent="$heroAccent" />
    </x-slot:header>

    {{ $slot }}

    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    @isset($note)
        <x-slot:note>
            {{ $note }}
        </x-slot:note>
    @endisset

    <x-slot:signoff>
        {{ filled($signoff) ? $signoff : 'Met vriendelijke groet, '.config('mail.brand.team') }}
    </x-slot:signoff>

    <x-slot:footer>
        <x-mail::footer :recipient="$recipient">
            U ontvangt deze e-mail naar aanleiding van uw deelname aan of betrokkenheid bij {{ config('app.name') }}.
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
