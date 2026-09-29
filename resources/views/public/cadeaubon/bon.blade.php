@php use App\Support\DutchTime; @endphp
<x-layouts.public :title="'Cadeaubon '.$voucher->code" description="Digitale cadeaubon van De Gouden Bol.">
    <x-slot:head><meta name="robots" content="noindex, nofollow"></x-slot:head>
    <section class="relative overflow-hidden pt-32 pb-16 print:pt-4">
        <div class="site-container max-w-[560px]">
            @if (session('status'))
                <x-ui.chip status="succes" :dot="true" class="mb-6 print:hidden">{{ session('status') }}</x-ui.chip>
            @endif
            <x-ui.card class="!p-8 text-center print:border-0 print:shadow-none">
                <div class="mb-4 flex justify-center"><x-ui.logo tagline="Cadeaubon" /></div>
                <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
                <div class="font-kop text-[3.4rem] leading-none font-bold text-espresso">{{ $voucher->formattedValue() }}</div>
                <div class="mt-4"><x-ui.chip :status="$status->chipStatus()" :dot="true">{{ $status->getLabel() }}</x-ui.chip></div>

                @if ($qr)
                    <div class="mx-auto my-6 w-[220px] [&_svg]:h-auto [&_svg]:w-full" aria-label="QR-code van de bon">{!! $qr !!}</div>
                @endif

                <p class="text-[0.7rem] font-bold tracking-[0.2em] text-gedempt uppercase">Bonnummer</p>
                <p class="font-kop text-[1.9rem] font-bold tracking-[0.12em] text-espresso">{{ $voucher->code }}</p>

                <dl class="mx-auto mt-6 grid max-w-[360px] gap-y-1.5 text-left text-[0.85rem] sm:grid-cols-[120px_1fr]">
                    <dt class="text-gedempt">Inwisselen bij</dt><dd class="font-semibold">{{ $company->name }}{{ $company->primaryLocation ? ', '.$company->primaryLocation->street.' '.$company->primaryLocation->house_number.', '.$company->primaryLocation->city : '' }}</dd>
                    <dt class="text-gedempt">Geldig t/m</dt><dd class="font-semibold">{{ DutchTime::date($voucher->expires_at) }}</dd>
                    @if ($voucher->redeemed_at)
                        <dt class="text-gedempt">Verzilverd op</dt><dd class="font-semibold">{{ DutchTime::format($voucher->redeemed_at, 'D MMMM YYYY, HH:mm') }}</dd>
                    @endif
                </dl>
                <p class="mt-6 text-[0.75rem] leading-relaxed text-gedempt">Toon deze pagina aan de kassa. De medewerker scant de QR-code of typt het bonnummer in. De bon is eenmalig; na verzilveren verandert de status hierboven.</p>
                <div class="mt-4 print:hidden"><x-ui.button type="button" variant="outline" size="sm" onclick="window.print()">Afdrukken</x-ui.button></div>
            </x-ui.card>
        </div>
    </section>
</x-layouts.public>
