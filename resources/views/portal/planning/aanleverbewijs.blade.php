@php use App\Support\DutchTime; @endphp
<x-layouts.portal title="Aanleverbewijs">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('portaal.planning', $company) }}" class="text-[0.85rem] text-goud-tekst underline">&larr; Terug naar de planning</a>
        <x-ui.button variant="outline" size="sm" type="button" onclick="window.print()">Afdrukken of opslaan als pdf</x-ui.button>
    </div>

    <x-ui.card class="mx-auto max-w-[640px] !p-10 text-center print:border-0 print:shadow-none">
        <div class="mb-6 flex justify-center"><x-ui.logo tagline="Aanleverbewijs {{ $edition?->year }}" /></div>
        <x-ui.eyebrow>Aanleverbewijs</x-ui.eyebrow>
        <h1 class="kop-2 mb-2">{{ $entry->public_name }}</h1>
        <p class="text-[0.9rem] text-gedempt">{{ $entry->province->name }}</p>

        <div class="mx-auto my-8 w-[240px] [&_svg]:h-auto [&_svg]:w-full" aria-label="QR-code met aanlevercode {{ $entry->delivery_code }}">{!! $qr !!}</div>

        <p class="text-[0.75rem] font-bold tracking-[0.2em] text-gedempt uppercase">Aanlevercode</p>
        <p class="font-kop text-[2.2rem] font-bold tracking-[0.14em] text-espresso">{{ $entry->delivery_code }}</p>

        <dl class="mx-auto mt-8 grid max-w-[420px] gap-x-6 gap-y-2 text-left text-[0.9rem] sm:grid-cols-[130px_1fr]">
            <dt class="text-gedempt">Wanneer</dt>
            <dd class="font-semibold">{{ ucfirst(DutchTime::format($entry->deliverySlot->starts_at, 'dddd D MMMM YYYY, HH:mm')) }} – {{ DutchTime::format($entry->deliverySlot->ends_at, 'HH:mm') }}</dd>
            @if ($entry->deliverySlot->testLocation)
                <dt class="text-gedempt">Waar</dt>
                <dd class="font-semibold">{{ $entry->deliverySlot->testLocation->name }}<br><span class="font-normal">{{ $entry->deliverySlot->testLocation->street }}, {{ $entry->deliverySlot->testLocation->postcode }} {{ $entry->deliverySlot->testLocation->city }}</span></dd>
            @endif
            <dt class="text-gedempt">Wat</dt>
            <dd class="font-semibold">{{ $edition?->settings->piecesPerEntry ?? 8 }} oliebollen met krenten en rozijnen, in neutrale verpakking zonder logo of naam</dd>
        </dl>

        <p class="mt-8 text-[0.8rem] leading-relaxed text-gedempt">Toon dit bewijs bij aankomst. Ontvangst scant de QR-code en legt tijd, temperatuur en aantal vast; daarna krijgt uw monster een anoniem testnummer. Uw naam komt niet in de testruimte.</p>
    </x-ui.card>
</x-layouts.portal>
