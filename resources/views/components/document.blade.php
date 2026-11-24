{{--
    A4-documenten in de opmaak van de aangeleverde sjablonen (Certificaat, Rapport en Persbericht, 24 nov 2026):
    roomkleurig vel, gouden kader met ronde hoeken, Cormorant Garamond en Manrope. De pagina toont een of meer
    vellen; "Download als pdf" opent het afdrukvenster van de browser, waar "Opslaan als pdf" de pdf maakt.
    Gouden tekst is #8A5A00 (goud-tekst); #C8860A is alleen lijn en accent.
--}}
@props([
    'title',
    'backUrl' => null,
    'backLabel' => 'Terug',
    'hint' => null,
])
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} – {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <style>
        @foreach ([[400, 'normal'], [400, 'italic'], [600, 'normal'], [600, 'italic']] as [$gewicht, $stijl])
        @font-face { font-family: 'Cormorant Garamond'; font-weight: {{ $gewicht }}; font-style: {{ $stijl }}; font-display: swap; src: url('{{ asset("fonts/cormorant-garamond-latin-{$gewicht}-{$stijl}.woff2") }}') format('woff2'); }
        @endforeach
        @foreach ([400, 500, 600, 700, 800] as $gewicht)
        @font-face { font-family: 'Manrope'; font-weight: {{ $gewicht }}; font-style: normal; font-display: swap; src: url('{{ asset("fonts/manrope-latin-{$gewicht}-normal.woff2") }}') format('woff2'); }
        @endforeach

        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #e9e2d5; color: #1c0f03; font-family: 'Manrope', Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .kop-font { font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif; }

        .balk { max-width: 210mm; margin: 0 auto; padding: 18px 16px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; font-size: 14px; }
        .balk a { color: #8a5a00; font-weight: 600; }
        .balk__hint { color: #6f6255; }
        .knop { background: #c8860a; color: #1c0f03; border: 0; border-radius: 30px; padding: 12px 22px; font: 800 12px 'Manrope', Arial, sans-serif; letter-spacing: .08em; cursor: pointer; text-transform: uppercase; }

        .vel { position: relative; width: 210mm; min-height: 297mm; margin: 0 auto 32px; padding: 11mm; background: #f9f4e9; overflow: hidden; display: flex; box-shadow: 0 10px 40px rgba(28, 15, 3, .14); break-after: page; zoom: var(--schaal, 1); -webkit-box-decoration-break: clone; box-decoration-break: clone; }
        .vel--vast { height: 297mm; }
        .vel__cirkel { position: absolute; border-radius: 50%; background: #fff1de; }
        .kader { position: relative; flex: 1; display: flex; border: 2px solid #c8860a; border-radius: 22px; padding: 2.4mm; -webkit-box-decoration-break: clone; box-decoration-break: clone; }
        .kader__binnen { flex: 1; min-width: 0; display: flex; flex-direction: column; border: 1px solid #ecd9a9; border-radius: 16px; -webkit-box-decoration-break: clone; box-decoration-break: clone; }

        .label { font-size: 8pt; font-weight: 800; letter-spacing: .2em; text-transform: uppercase; color: #8a5a00; }
        .label--grijs { color: #7a6b58; }
        .merk { display: flex; align-items: center; gap: 4.5mm; }
        .merk__beeld { flex: none; border: 2px solid #c8860a; border-radius: 50%; background: #fff8ec; display: flex; align-items: center; justify-content: center; }
        .merk__beeld img { width: 82%; height: 82%; object-fit: contain; display: block; }
        .merk__naam { font-size: 19pt; font-weight: 600; line-height: 1.05; }
        .merk__sub { margin-top: 1mm; font-size: 7.5pt; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; color: #7a6b58; }
        .site { text-align: center; font-size: 8pt; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: #5c4d3c; }
        .motto { text-align: center; font-size: 13.5pt; font-style: italic; font-weight: 600; color: #8a5a00; }

        @media print {
            html, body { background: #f9f4e9; }
            .balk { display: none; }
            .vel { box-shadow: none; margin: 0; zoom: 1; }
            .vel:last-of-type { break-after: auto; }
        }
    </style>
    {{ $styles ?? '' }}
</head>
<body>
    <div class="balk">
        @if ($backUrl)<a href="{{ $backUrl }}">&larr; {{ $backLabel }}</a>@else<span></span>@endif
        @if ($hint)<span class="balk__hint">{{ $hint }}</span>@endif
        <button type="button" class="knop" onclick="window.print()">Download als pdf</button>
    </div>

    {{ $slot }}

    {{-- Op een smal scherm het vel verkleinen tot het past; afdrukken blijft op ware grootte. --}}
    <script>
        (function () {
            var schaal = function () { document.documentElement.style.setProperty('--schaal', Math.min(1, (window.innerWidth - 16) / 794).toFixed(3)); };
            schaal();
            window.addEventListener('resize', schaal);
        })();
    </script>
</body>
</html>
