<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Etiket {{ $sample->sample_number }}</title>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <style>
        @page { size: 62mm 29mm; margin: 0; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; font-family: Manrope, Arial, sans-serif; }
        .etiket { width: 62mm; height: 29mm; box-sizing: border-box; padding: 2mm 3mm; display: flex; flex-direction: column; justify-content: center; align-items: center; page-break-after: always; }
        .etiket__nummer { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 700; font-size: 15mm; line-height: 1; letter-spacing: 0.06em; font-variant-numeric: lining-nums tabular-nums; }
        .etiket__meta { margin-top: 1.5mm; font-size: 2.6mm; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; }
        .scherm { display: none; }
        @media screen {
            body { background: #F0E8D5; padding: 32px; }
            .etiket { background: #fff; border: 1px dashed #A89A85; margin: 0 auto 16px; transform: scale(2); transform-origin: top center; }
            .scherm { display: block; max-width: 520px; margin: 160px auto 0; text-align: center; font-size: 14px; color: #6B5B4A; }
            .scherm button { margin-top: 12px; padding: 12px 24px; border: 0; border-radius: 999px; background: #C8860A; color: #1C0F03; font-weight: 800; cursor: pointer; }
        }
    </style>
</head>
<body onload="window.print()">
    @for ($i = 0; $i < 2; $i++)
        <div class="etiket">
            <div class="etiket__nummer">{{ $sample->sample_number }}</div>
            <div class="etiket__meta">De Gouden Bol {{ $edition?->year }} · {{ $sample->round->getLabel() }}</div>
        </div>
    @endfor
    <div class="scherm">
        Twee etiketten van 62 × 29 mm (bakje en deksel). Alleen het testnummer staat erop.
        <br><button type="button" onclick="window.print()">Opnieuw afdrukken</button>
    </div>
</body>
</html>
