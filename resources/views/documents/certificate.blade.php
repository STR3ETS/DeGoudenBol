{{-- Certificaat van deelname, naar het aangeleverde sjabloon. Eén vel per inschrijving. --}}
@php
    use App\Support\DutchTime;

    $beeld = asset('site/images/5097bdefb5478dc6e6537a40211dbf6432a82738.png');
    $site = mb_strtoupper((string) config('marketing.site_label'));
@endphp
<x-document :title="$title" :back-url="$backUrl" :back-label="$backLabel" :hint="$hint ?? null">
    <x-slot:styles>
        <style>
            .cert { align-items: center; text-align: center; padding: 16mm 20mm 9mm; }
            .cert .merk__beeld { width: 31mm; height: 31mm; margin-bottom: 9mm; }
            .cert__titel { margin: 5mm 0 4mm; font-size: 64pt; font-weight: 400; line-height: 1; letter-spacing: -.01em; }
            .cert__titel em { color: #8a5a00; font-weight: 600; }
            .cert__ruit { display: flex; align-items: center; gap: 4mm; margin: 8mm 0 9mm; color: #c8860a; font-size: 7pt; }
            .cert__ruit::before, .cert__ruit::after { content: ''; width: 26mm; height: 1px; background: #e2c88f; }
            .cert__intro { font-size: 11.5pt; color: #6f6255; }
            .cert__naam { width: 140mm; min-height: 22mm; margin-top: 5mm; padding: 0 4mm 2.5mm; display: flex; align-items: flex-end; justify-content: center; border-bottom: 1px solid #cfc6b6; font-size: 31pt; font-style: italic; font-weight: 600; line-height: 1.05; overflow-wrap: anywhere; }
            .cert__naam--lang { font-size: 23pt; }
            .cert__tekst { margin: 6mm auto 0; max-width: 135mm; font-size: 11.5pt; line-height: 1.75; }
            .cert__kaart { width: 100%; margin-top: 9mm; padding: 7mm 10mm 8mm; border: 1.5px solid #c8860a; border-radius: 16px; background: #fff1de; box-shadow: 0 10px 26px rgba(138, 90, 0, .12); }
            .cert__sterren { font-size: 10pt; letter-spacing: .5em; color: #8a5a00; }
            .cert__motto { margin-top: 3mm; font-size: 24pt; font-style: italic; font-weight: 600; color: #8a5a00; }
            .cert__sub { margin-top: 1.5mm; font-size: 17pt; font-weight: 600; line-height: 1.35; }
            .cert__tekens { width: 100%; margin-top: auto; padding-top: 10mm; display: grid; grid-template-columns: 1fr 1fr; gap: 19mm; text-align: left; }
            .cert__waarde { min-height: 9mm; padding: 0 1mm 1.5mm; display: flex; align-items: flex-end; border-bottom: 1px solid #1c0f03; font-size: 14pt; font-weight: 600; }
            .cert__tekens .label { display: block; margin-top: 3mm; letter-spacing: .14em; }
            .cert .site { margin-top: 8mm; }
        </style>
    </x-slot:styles>

    @foreach ($entries as $entry)
        @php $datum = $entry->confidentialReport?->tested_on ?? $entry->confirmed_at; @endphp
        <article class="vel vel--vast">
            <div class="vel__cirkel" style="width: 164mm; height: 164mm; top: -48mm; left: 23mm;"></div>
            <div class="kader"><div class="kader__binnen cert">
                <div class="merk__beeld"><img src="{{ $beeld }}" alt=""></div>
                <div class="label">&#9733; Certificaat van deelname &#9733;</div>
                <h1 class="cert__titel kop-font">De Gouden <em>Bol</em></h1>
                <div class="label label--grijs">Editie {{ $entry->edition->year }}</div>
                <div class="cert__ruit" aria-hidden="true">&#9670;</div>
                <div class="cert__intro">Hierbij verklaren wij dat</div>
                <div class="cert__naam kop-font {{ mb_strlen($entry->public_name) > 30 ? 'cert__naam--lang' : '' }}">{{ $entry->public_name }}</div>
                <p class="cert__tekst">heeft deelgenomen aan De Gouden Bol, de blinde zoektocht naar de beste oliebol van Nederland.</p>
                <div class="cert__kaart">
                    <div class="cert__sterren" aria-hidden="true">&#9733;&#9733;&#9733;</div>
                    <div class="cert__motto kop-font">Naam eraf, nummer erop.</div>
                    <div class="cert__sub kop-font">Beoordeeld op deeg, geduld,<br>vakmanschap en kwaliteit.</div>
                </div>
                <div class="cert__tekens">
                    <div><div class="cert__waarde kop-font">{{ $entry->edition->settings->certificateSignatory }}</div><span class="label label--grijs">Namens de jury</span></div>
                    <div><div class="cert__waarde kop-font">{{ $datum ? DutchTime::format($datum, 'D MMMM YYYY') : '' }}</div><span class="label label--grijs">Datum</span></div>
                </div>
                <div class="site">&#9733;&nbsp; {{ $site }} &nbsp;&#9733;</div>
            </div></div>
        </article>
    @endforeach
</x-document>
