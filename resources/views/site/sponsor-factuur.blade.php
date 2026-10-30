<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Factuur {{ $invoice->number }} – {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-room">
    <div class="mx-auto max-w-[800px] px-4 py-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('sponsor.dashboard') }}" class="text-[0.85rem] text-goud-tekst underline">&larr; Terug naar uw sponsoromgeving</a>
            <x-ui.button variant="outline" size="sm" type="button" onclick="window.print()">Afdrukken of opslaan als pdf</x-ui.button>
        </div>
        <x-ui.card class="!p-10 print:border-0 print:shadow-none">
            @include('facturen.document')
        </x-ui.card>
    </div>
</body>
</html>
