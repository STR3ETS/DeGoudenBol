@php use App\Support\DutchTime; @endphp
<x-layouts.portal title="Facturen">
    <x-ui.eyebrow>Administratie</x-ui.eyebrow>
    <h1 class="kop-pagina mb-8">Uw <em>facturen</em></h1>

    @if ($invoices->isEmpty())
        <x-ui.empty-state titel="Nog geen facturen" tekst="Zodra een betaling is ontvangen verschijnt de factuur hier." />
    @else
        <x-ui.card class="!p-0 overflow-hidden">
            <table class="w-full text-[0.9rem]">
                <thead class="bg-zand text-left text-[0.68rem] font-bold tracking-[0.12em] text-gedempt uppercase">
                    <tr>
                        <th class="px-5 py-3">Factuur</th>
                        <th class="px-5 py-3">Datum</th>
                        <th class="px-5 py-3">Omschrijving</th>
                        <th class="px-5 py-3 text-right">Bedrag</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <tr class="border-t border-rand">
                            <td class="px-5 py-3 font-semibold">{{ $invoice->number }}</td>
                            <td class="px-5 py-3">{{ DutchTime::date($invoice->issued_at) }}</td>
                            <td class="px-5 py-3">{{ $invoice->order->lines->first()?->description }}</td>
                            <td class="px-5 py-3 text-right">{{ $invoice->formattedTotal() }}</td>
                            <td class="px-5 py-3"><x-ui.chip :status="$invoice->status->value === 'paid' ? 'succes' : 'waarschuwing'">{{ $invoice->status->getLabel() }}</x-ui.chip></td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('portaal.facturen.toon', $invoice) }}" class="text-goud-tekst underline">Bekijken</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.card>
    @endif
</x-layouts.portal>
