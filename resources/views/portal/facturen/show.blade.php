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
        <div class="mb-10 flex flex-wrap items-start justify-between gap-6">
            <x-ui.logo tagline="Oliebollenkeuring" />
            <div class="text-right">
                <div class="kop-3 text-[1.6rem]">Factuur</div>
                <div class="text-[0.9rem]">{{ $invoice->number }}</div>
                <div class="text-[0.8rem] text-gedempt">{{ DutchTime::date($invoice->issued_at) }}</div>
            </div>
        </div>

        <div class="mb-10 grid gap-8 text-[0.85rem] sm:grid-cols-2">
            <div>
                <div class="label">Aan</div>
                <div class="font-semibold">{{ $invoice->billing_name }}</div>
                @if ($invoice->billing_address)
                    <div>{{ $invoice->billing_address['street'] ?? '' }}</div>
                    <div>{{ $invoice->billing_address['postcode'] ?? '' }} {{ $invoice->billing_address['city'] ?? '' }}</div>
                    @if (! empty($invoice->billing_address['kvk']))
                        <div class="text-gedempt">KvK {{ $invoice->billing_address['kvk'] }}</div>
                    @endif
                @endif
            </div>
            <div>
                <div class="label">Van</div>
                <div class="font-semibold">{{ $issuer['name'] }}</div>
                <div>{{ $issuer['address'] }}</div>
                <div class="text-gedempt">KvK {{ $issuer['kvk'] }} · btw {{ $issuer['vat'] }}</div>
                <div class="text-gedempt">{{ $issuer['email'] }}</div>
            </div>
        </div>

        <table class="mb-6 w-full text-[0.9rem]">
            <thead class="text-left text-[0.68rem] font-bold tracking-[0.12em] text-gedempt uppercase">
                <tr>
                    <th class="border-b border-rand-sterk py-2">Omschrijving</th>
                    <th class="border-b border-rand-sterk py-2 text-right">Aantal</th>
                    <th class="border-b border-rand-sterk py-2 text-right">Prijs excl.</th>
                    <th class="border-b border-rand-sterk py-2 text-right">Btw</th>
                    <th class="border-b border-rand-sterk py-2 text-right">Totaal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->order->lines as $line)
                    <tr>
                        <td class="border-b border-rand py-3">{{ $line->description }}</td>
                        <td class="border-b border-rand py-3 text-right">{{ $line->quantity }}</td>
                        <td class="border-b border-rand py-3 text-right">{{ Money::format($line->subtotal_cents) }}</td>
                        <td class="border-b border-rand py-3 text-right">{{ rtrim(rtrim(number_format($line->vat_rate, 2, ',', '.'), '0'), ',') }}%</td>
                        <td class="border-b border-rand py-3 text-right">{{ Money::format($line->total_cents) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ml-auto max-w-[320px] text-[0.9rem]">
            <div class="flex justify-between py-1"><span class="text-gedempt">Subtotaal excl. btw</span><span>{{ Money::format($invoice->subtotal_cents) }}</span></div>
            <div class="flex justify-between py-1"><span class="text-gedempt">Btw</span><span>{{ Money::format($invoice->vat_cents) }}</span></div>
            <div class="flex justify-between border-t border-rand-sterk py-2 font-bold"><span>Totaal</span><span>{{ Money::format($invoice->total_cents) }}</span></div>
            <div class="mt-2 text-[0.8rem] text-gedempt">
                @if ($invoice->paid_at)
                    Betaald op {{ DutchTime::format($invoice->paid_at, 'D MMMM YYYY') }} via {{ $invoice->order->latestPayment?->method ?? 'online betaling' }}.
                @else
                    Te betalen vóór {{ DutchTime::date($invoice->due_at) }}.
                @endif
            </div>
        </div>

        <p class="mt-10 text-[0.75rem] text-gedempt">{{ $issuer['footer'] }}</p>
    </x-ui.card>
</x-layouts.portal>
