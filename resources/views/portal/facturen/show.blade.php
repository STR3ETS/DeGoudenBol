@php
    use App\Support\DutchTime;
    use App\Support\Money;
@endphp
<x-layouts.portal :title="'Factuur '.$invoice->number">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('portaal.facturen') }}" class="text-[0.85rem] text-goud-tekst underline">&larr; Alle facturen</a>
        <x-ui.button variant="outline" size="sm" type="button" onclick="window.print()">Afdrukken of opslaan als pdf</x-ui.button>
    </div>

    <x-ui.card class="mx-auto max-w-[800px] !p-10 print:border-0 print:shadow-none">
        @include('facturen.document')
    </x-ui.card>
</x-layouts.portal>
