@php use App\Support\DutchTime; @endphp
<x-layouts.portal title="Scanner">
    <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-6">Cadeaubon <em>verzilveren</em></h1>

    @if ($outcome)
        <x-ui.card :variant="$outcome['result'] === 'redeemed' ? 'zand' : 'wit'" class="mb-6 text-center" role="status" aria-live="polite">
            <x-ui.chip :status="$outcome['chip']" :dot="true" class="!text-[1rem]">{{ $outcome['title'] }}</x-ui.chip>
            <p class="mt-4 font-kop leading-tight font-semibold text-espresso {{ $outcome['result'] === 'redeemed' ? 'text-[2.2rem]' : 'text-[1.5rem]' }}">{{ $outcome['text'] }}</p>
            @if ($outcome['code'])<p class="mt-2 font-mono text-[0.9rem] text-gedempt">{{ $outcome['code'] }}</p>@endif
            @if ($outcome['photo_allowed'] && $outcome['redemption'])
                <form method="post" action="{{ route('portaal.scan.foto', [$company, $outcome['redemption']]) }}" enctype="multipart/form-data" class="mt-5 flex flex-wrap items-center justify-center gap-3">
                    @csrf
                    <label class="text-[0.85rem] text-gedempt">Verzilverfoto (winnaar gaf toestemming): <input type="file" name="photo" accept="image/*" capture="environment" class="ml-2"></label>
                    <x-ui.button type="submit" variant="outline" size="sm">Foto bewaren</x-ui.button>
                </form>
            @endif
        </x-ui.card>
    @endif

    <x-ui.card class="mb-6" data-scan-form>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="kop-3 text-[1.2rem]">Scan de QR-code</h2>
            <x-ui.button type="button" variant="outline" size="sm" data-scan-start>Camera starten</x-ui.button>
        </div>
        <div class="hidden overflow-hidden rounded-kaart bg-espresso" data-scan-video-wrap><video class="aspect-video w-full object-cover" data-scan-video muted playsinline></video></div>
        <form method="post" action="{{ route('portaal.scan.verzilveren', $company) }}" class="mt-4 flex flex-wrap items-end gap-3" data-scan-submit>
            @csrf
            <input type="hidden" name="method" value="manual" data-scan-method>
            <x-ui.field name="code" label="Of typ het bonnummer (noodroute)" placeholder="GB26-GLD-7K3M" class="min-w-[240px] flex-1" autocomplete="off" data-scan-code />
            <x-ui.button type="submit">Verzilveren</x-ui.button>
        </form>
        <p class="mt-3 text-[0.78rem] text-gedempt">Alleen bonnen die door {{ $company->name }} zijn uitgegeven kunnen hier worden verzilverd. Eén keer geldig; twee telefoons tegelijk kunnen nooit dubbel verzilveren.</p>
    </x-ui.card>

    @if ($recent->isNotEmpty())
        <x-ui.card class="!p-0 overflow-hidden">
            <table class="w-full text-[0.85rem]">
                <thead class="bg-zand text-left text-[0.68rem] font-bold tracking-[0.12em] text-gedempt uppercase"><tr><th class="px-5 py-3">Moment</th><th class="px-5 py-3">Bon</th><th class="px-5 py-3">Resultaat</th></tr></thead>
                <tbody>
                    @foreach ($recent as $redemption)
                        <tr class="border-t border-rand">
                            <td class="px-5 py-2.5">{{ DutchTime::format($redemption->created_at, 'D MMM, HH:mm') }}</td>
                            <td class="px-5 py-2.5 font-mono">{{ $redemption->voucher?->code ?? '–' }}</td>
                            <td class="px-5 py-2.5">@if ($redemption->wasRedeemed())<x-ui.chip status="succes">Verzilverd</x-ui.chip>@else<x-ui.chip status="waarschuwing">Geweigerd · {{ $redemption->refusalLabel() }}</x-ui.chip>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.card>
    @endif
</x-layouts.portal>
