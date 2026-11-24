{{-- Persbericht, naar het aangeleverde sjabloon. Loopt bij een lange tekst door op een volgende pagina. --}}
@php
    $beeld = asset('site/images/5097bdefb5478dc6e6537a40211dbf6432a82738.png');
    $site = (string) config('marketing.site_label');
    $release = $document->release;
    $contact = $document->contactLines();
@endphp
<x-document :title="$title" :back-url="$backUrl" :back-label="$backLabel" :hint="$hint ?? null">
    <x-slot:styles>
        <style>
            .pers { padding: 10mm 14mm 9mm; }
            .pers__kop { display: flex; align-items: center; justify-content: space-between; gap: 6mm; padding-bottom: 6mm; border-bottom: 1px solid #ecd9a9; }
            .pers .merk__beeld { width: 15mm; height: 15mm; }
            .pers__embargo { margin-top: 6mm; padding: 2.5mm 4mm; border-radius: 8px; background: #fbe9d9; font-size: 8.5pt; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #8c2f0b; }
            .pers__titel { margin: 10mm 0 0; font-size: 30pt; font-weight: 400; line-height: 1.12; }
            .pers__subkop { margin: 4mm 0 0; font-size: 15.5pt; font-style: italic; font-weight: 600; color: #8a5a00; line-height: 1.3; }
            .pers__tekst { margin-top: 8mm; font-size: 10.5pt; line-height: 1.75; }
            .pers__tekst p { margin: 0 0 4.5mm; white-space: pre-line; orphans: 3; widows: 3; }
            .pers__tekst h2 { margin: 6mm 0 1.5mm; font-size: 8.5pt; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #8a5a00; break-after: avoid; }
            .pers__chip { display: inline-block; padding: 0 2mm; border-radius: 4px; background: #fbe6b8; font-weight: 800; color: #5c3c00; }
            .pers__citaat { margin: 3mm 0 7mm; padding: 7mm 9mm; border: 1.5px solid #c8860a; border-radius: 16px; background: #fff1de; box-shadow: 0 10px 26px rgba(138, 90, 0, .1); break-inside: avoid; }
            .pers__citaat blockquote { margin: 0; font-size: 18pt; font-style: italic; font-weight: 600; line-height: 1.35; color: #8a5a00; }
            .pers__citaat .label { display: block; margin-top: 3mm; letter-spacing: .12em; }
            .pers__einde { margin-top: 3mm; }
            .pers__noot { margin-top: auto; padding-top: 8mm; break-inside: avoid; }
            .pers__nootvak { padding: 6mm 7mm; border-radius: 14px; background: #efe6d3; }
            .pers__nootkop { font-size: 8pt; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
            .pers__nootkop span { font-weight: 600; color: #7a6b58; }
            .pers__kolommen { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7mm; margin-top: 3.5mm; font-size: 9pt; line-height: 1.7; }
            .pers__kolommen b { display: block; font-weight: 800; }
            .pers__kolommen a { color: #8a5a00; font-weight: 700; overflow-wrap: anywhere; }
        </style>
    </x-slot:styles>

    <article class="vel">
        <div class="vel__cirkel" style="width: 150mm; height: 150mm; top: -72mm; right: -52mm;"></div>
        <div class="kader"><div class="kader__binnen pers">
            <header class="pers__kop">
                <div class="merk">
                    <div class="merk__beeld"><img src="{{ $beeld }}" alt=""></div>
                    <div><div class="merk__naam kop-font">De Gouden Bol</div><div class="merk__sub">Oliebollen test</div></div>
                </div>
                <div class="label">&#9733; Persbericht &#9733;</div>
            </header>

            @if ($document->embargo)
                <div class="pers__embargo">{{ $document->embargo }}</div>
            @endif

            <h1 class="pers__titel kop-font">{{ $release->title }}</h1>
            @if (filled($release->subtitle))
                <p class="pers__subkop kop-font">{{ $release->subtitle }}</p>
            @endif

            <div class="pers__tekst">
                @if ($document->lead)
                    <p><span class="pers__chip">{{ mb_strtoupper($document->place) }}</span>,&nbsp;<span class="pers__chip">{{ mb_strtoupper($document->date) }}</span> &mdash; {{ $document->lead }}</p>
                @endif

                @if ($document->hasQuote())
                    <div class="pers__citaat">
                        <blockquote class="kop-font">&ldquo;{{ $release->quote }}&rdquo;</blockquote>
                        @if (filled($release->quote_name))
                            <span class="label label--grijs">&mdash; {{ $release->quote_name }}@if (filled($release->quote_role)), {{ $release->quote_role }}@endif</span>
                        @endif
                    </div>
                @endif

                @foreach ($document->blocks as $block)
                    @if ($block['heading'])<h2>{{ $block['heading'] }}</h2>@endif
                    <p>{{ $block['text'] }}</p>
                @endforeach

                <p>Meer informatie op <a href="{{ $document->moreUrl }}" style="color: #8a5a00; font-weight: 700;">{{ $site }}</a>.</p>
                <div class="label pers__einde">&#9733; Einde persbericht &#9733;</div>
            </div>

            <div class="pers__noot">
                <div class="pers__nootvak">
                    <div class="pers__nootkop">Noot voor de redactie <span>· niet voor publicatie</span></div>
                    <div class="pers__kolommen">
                        @if ($contact !== [])
                            <div><b>Contact</b>@foreach ($contact as $regel){{ $regel }}<br>@endforeach</div>
                        @endif
                        <div><b>Beeld en cijfers</b><a href="{{ $document->moreUrl }}">{{ preg_replace('#^https?://#', '', $document->moreUrl) }}</a></div>
                        <div><b>Over De Gouden Bol</b>De onafhankelijke, blinde zoektocht naar de beste oliebol van Nederland.</div>
                    </div>
                </div>
            </div>
        </div></div>
    </article>
</x-document>
