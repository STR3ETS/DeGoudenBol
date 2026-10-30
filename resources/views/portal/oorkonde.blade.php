@php use App\Support\DutchTime; @endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Oorkonde {{ $company->name }} – {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #eee9e1; color: #23180e; font-family: Georgia, 'Times New Roman', serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .balk { max-width: 210mm; margin: 0 auto; padding: 18px 16px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; font: 14px Arial, Helvetica, sans-serif; }
        .balk a { color: #8a5a00; }
        .knop { background: #cf8c08; color: #1d140c; border: 0; border-radius: 30px; padding: 12px 22px; font-weight: 700; font-size: 13px; letter-spacing: .4px; cursor: pointer; text-transform: uppercase; }
        .vel { width: 210mm; height: 297mm; margin: 0 auto 40px; background: radial-gradient(circle at 50% 30%, #3a2814 0%, #1e1207 70%); padding: 12mm; box-shadow: 0 10px 40px rgba(35, 24, 14, .2); }
        .lijst { height: 100%; border: 2px solid #c8963a; outline: 1px solid rgba(200, 150, 58, .45); outline-offset: -8px; border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 18mm 16mm; }
        .ster { width: 74px; height: 74px; border: 2px solid #d79819; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dfa329; font-size: 40px; margin-bottom: 26px; }
        .oorkonde { color: #f6dc9c; font-size: 64px; letter-spacing: 6px; text-transform: uppercase; margin: 0; }
        .ondertitel { color: #e9cf95; font: 700 13px Arial, Helvetica, sans-serif; letter-spacing: 4px; text-transform: uppercase; margin-top: 14px; }
        .scheiding { width: 70px; height: 1px; background: #b58225; margin: 34px auto; }
        .voor { color: #cdb894; font-size: 18px; font-style: italic; }
        .naam { color: #fff6e7; font-size: 46px; line-height: 1.15; margin: 14px 0 6px; }
        .plaats { color: #d8c3a0; font: 15px Arial, Helvetica, sans-serif; letter-spacing: 1px; }
        .tekst { color: #efe2c8; font-size: 20px; line-height: 1.55; max-width: 120mm; margin: 34px auto 0; }
        .tekst em { color: #e1ad47; }
        .voet { margin-top: auto; padding-top: 30px; color: #b69c7b; font: 12px Arial, Helvetica, sans-serif; letter-spacing: 1.6px; text-transform: uppercase; }
        @media print { html, body { background: #fff; } .balk { display: none; } .vel { box-shadow: none; margin: 0; } }
        @media (max-width: 820px) { .vel { width: 100%; height: auto; min-height: 140vw; } .oorkonde { font-size: 40px; } .naam { font-size: 30px; } }
    </style>
</head>
<body>
    <div class="balk">
        <a href="{{ route('portaal.dashboard') }}">&larr; Terug naar het portaal</a>
        <button type="button" class="knop" onclick="window.print()">Download als pdf</button>
    </div>

    <article class="vel">
        <div class="lijst">
            <div class="ster" aria-hidden="true">&#9733;</div>
            <h1 class="oorkonde">Oorkonde</h1>
            <div class="ondertitel">Officiële deelnemer {{ config('app.name') }} {{ $edition?->year }}</div>
            <div class="scheiding"></div>
            <div class="voor">uitgereikt aan</div>
            <div class="naam">{{ $entry->public_name }}</div>
            <div class="plaats">{{ $company->primaryLocation?->city ? $company->primaryLocation->city.' · ' : '' }}{{ $entry->province?->name }}</div>
            <p class="tekst">Voor deelname aan <em>{{ config('app.name') }} {{ $edition?->year }}</em>, de onafhankelijke keuring op zoek naar de beste oliebol van Nederland.</p>
            <div class="voet">{{ preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')) }} &nbsp;·&nbsp; {{ $entry->confirmed_at ? DutchTime::format($entry->confirmed_at, 'D MMMM YYYY') : '' }}</div>
        </div>
    </article>
</body>
</html>
