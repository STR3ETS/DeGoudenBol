<x-layouts.public title="Testbetaling">
    <div class="site-container pt-36 pb-24">
        <div class="mx-auto max-w-[560px]">
            <x-ui.chip status="waarschuwing" class="mb-4">Lokale testbetaling, geen echte transactie</x-ui.chip>
            <h1 class="kop-pagina mb-4">Betaling <em>simuleren</em></h1>
            <p class="body-text mb-8">Order {{ $payment->order->number }} voor {{ $payment->order->formattedTotal() }}. Kies hieronder hoe de betaling afloopt; op productie neemt Mollie deze stap over.</p>

            <x-ui.card class="mb-6">
                <ul class="mb-4 flex flex-col gap-2 text-[0.9rem]">
                    @foreach ($payment->order->lines as $line)
                        <li class="flex justify-between gap-4"><span>{{ $line->description }}</span><span class="font-semibold">{{ \App\Support\Money::format($line->total_cents) }}</span></li>
                    @endforeach
                </ul>
                <div class="flex justify-between border-t border-rand pt-3 font-bold"><span>Totaal incl. btw</span><span>{{ $payment->order->formattedTotal() }}</span></div>
            </x-ui.card>

            <div class="flex flex-wrap gap-3">
                <form method="post" action="{{ route('betaling.fake.afronden', $payment) }}">
                    @csrf
                    <input type="hidden" name="status" value="paid">
                    <x-ui.button type="submit">Betaling geslaagd</x-ui.button>
                </form>
                <form method="post" action="{{ route('betaling.fake.afronden', $payment) }}">
                    @csrf
                    <input type="hidden" name="status" value="failed">
                    <x-ui.button type="submit" variant="outline">Betaling mislukt</x-ui.button>
                </form>
                <form method="post" action="{{ route('betaling.fake.afronden', $payment) }}">
                    @csrf
                    <input type="hidden" name="status" value="canceled">
                    <x-ui.button type="submit" variant="ghost">Annuleren</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.public>
