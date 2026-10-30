@php
    use App\Support\DutchTime;
    use App\Support\Portal\PersonalReport;

    $entry = $report->entry;
    $text = $report->report;
    $toelichting = collect([
        ['Sterke punten', $text->strengths],
        ['Verbeterpunten', $text->opportunities],
        ['Algemene indruk', $text->general_impression],
    ])->filter(fn ($blok) => filled($blok[1]));
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Beoordelingsrapport {{ $company->name }} – {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #eee9e1; color: #23180e; font-family: 'Helvetica Neue', Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .balk { max-width: 210mm; margin: 0 auto; padding: 18px 16px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; font-size: 14px; }
        .balk a { color: #8a5a00; }
        .knop { background: #cf8c08; color: #1d140c; border: 0; border-radius: 30px; padding: 12px 22px; font-weight: 700; font-size: 13px; letter-spacing: .4px; cursor: pointer; text-transform: uppercase; }
        .vel { width: 210mm; min-height: 297mm; margin: 0 auto 40px; background: #fff; box-shadow: 0 10px 40px rgba(35, 24, 14, .12); display: flex; flex-direction: column; }
        .kop { background: #231a10; padding: 34px 44px 30px; border-bottom: 7px solid #b58a36; }
        .kop__label { color: #f1d9a4; font-weight: 700; font-size: 15px; letter-spacing: 1.4px; text-transform: uppercase; }
        .kop__naam { color: #f6dc9c; font-family: Georgia, 'Times New Roman', serif; font-weight: 700; font-size: 40px; letter-spacing: 1px; margin: 12px 0 10px; text-transform: uppercase; }
        .kop__sub { color: #efe6d6; font-size: 14px; }
        .inhoud { padding: 30px 44px 0; flex: 1; }
        .monster { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; align-items: start; }
        .monster img { width: 100%; height: auto; display: block; }
        h2 { color: #b58a36; font-size: 15px; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 14px; }
        h3 { font-size: 17px; letter-spacing: .6px; text-transform: uppercase; margin: 28px 0 4px; }
        .uitleg { color: #6f6255; font-size: 12.5px; margin: 0 0 16px; }
        .gegevens { display: grid; grid-template-columns: 130px 1fr; gap: 12px 10px; font-size: 13px; }
        .gegevens dt { color: #6f6255; }
        .gegevens dd { margin: 0; border-bottom: 1px solid #e3d3b4; padding-bottom: 6px; font-weight: 600; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; }
        thead th { background: #b58a36; color: #fff; text-align: left; padding: 14px 18px; font-size: 12.5px; letter-spacing: 1px; text-transform: uppercase; }
        thead th:first-child { border-radius: 8px 0 0 0; }
        thead th:last-child { border-radius: 0 8px 0 0; }
        tbody td { padding: 12px 18px; border-bottom: 1px solid #efe3cc; }
        tbody tr:nth-child(odd) td { background: #f8f2e6; }
        .getal { text-align: center; font-weight: 700; width: 110px; }
        .totaal { margin-top: 16px; background: #f2e5c9; border-radius: 8px; padding: 16px 18px; display: flex; justify-content: space-between; align-items: center; font-weight: 700; }
        .totaal__label { font-size: 17px; letter-spacing: .6px; text-transform: uppercase; }
        .totaal__score { font-family: Georgia, 'Times New Roman', serif; font-size: 26px; }
        .totaal__score small { font-family: inherit; font-size: 14px; margin-left: 6px; }
        .cijfer { font-size: 12.5px; color: #6f6255; margin-top: 6px; text-align: right; }
        .blok { margin-top: 10px; }
        .blok h4 { margin: 14px 0 4px; font-size: 13px; color: #8a5a00; text-transform: uppercase; letter-spacing: .8px; }
        .blok p { margin: 0; font-size: 13.5px; line-height: 1.65; white-space: pre-line; }
        .voet { margin: 26px 44px 0; padding: 14px 0 24px; border-top: 2px solid #b58a36; display: flex; justify-content: space-between; gap: 16px; font-size: 12px; }
        .voet strong { color: #b58a36; }
        .voet span { color: #6f6255; display: block; margin-top: 6px; }
        @media print { html, body { background: #fff; } .balk { display: none; } .vel { box-shadow: none; margin: 0; } }
        @media (max-width: 820px) { .vel { width: 100%; min-height: 0; } .monster { grid-template-columns: 1fr; } .kop, .inhoud { padding-left: 20px; padding-right: 20px; } .voet { margin: 26px 20px 0; flex-direction: column; } .kop__naam { font-size: 28px; } }
    </style>
</head>
<body>
    <div class="balk">
        <a href="{{ route('portaal.uitslag', $company) }}">&larr; Terug naar het portaal</a>
        <button type="button" class="knop" onclick="window.print()">Download als pdf</button>
    </div>

    <article class="vel">
        <header class="kop">
            <div class="kop__label">Officieel beoordelingsrapport</div>
            <div class="kop__naam">{{ config('app.name') }}</div>
            <div class="kop__sub">Onafhankelijke productbeoordeling &nbsp;|&nbsp; Uitslag per ingezonden product</div>
        </header>

        <div class="inhoud">
            <section class="monster">
                <img src="{{ asset('images/rapport-oliebollen.jpg') }}" alt="Doorgesneden en hele oliebollen">
                <div>
                    <h2>Deelnemer &amp; monster</h2>
                    <dl class="gegevens">
                        <dt>Bedrijf / deelnemer</dt><dd>{{ $company->name }}</dd>
                        <dt>Vestigingsplaats</dt><dd>{{ $report->city() ?? '–' }}</dd>
                        <dt>Product / variant</dt><dd>{{ $entry->productLabel() }}</dd>
                        <dt>Testdatum</dt><dd>{{ $text->tested_on ? DutchTime::format($text->tested_on, 'D MMMM YYYY') : '–' }}</dd>
                        @if ($entry->delivery_code)
                            <dt>Deelnemercode</dt><dd>{{ $entry->delivery_code }}</dd>
                        @endif
                    </dl>
                </div>
            </section>

            <h3>Beoordeling per onderdeel</h3>
            <p class="uitleg">De testonderdelen zijn gewogen tot een maximale totaalscore van 100 punten.@if ($report->cardCount) Gemiddelde van {{ $report->cardCount }} geldige scorekaarten van het onafhankelijke panel.@endif</p>

            <table>
                <thead><tr><th>Beoordelingsonderdeel</th><th class="getal">Score</th><th class="getal">Max.</th></tr></thead>
                <tbody>
                    @foreach ($report->criteria as $criterion)
                        <tr><td>{{ $criterion['name'] }}</td><td class="getal">{{ PersonalReport::number($criterion['score']) }}</td><td class="getal">{{ $criterion['max'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totaal">
                <span class="totaal__label">Totaalscore</span>
                <span class="totaal__score">{{ PersonalReport::number($report->totalPoints) }}<small>/ 100 punten</small></span>
            </div>
            @if ($report->grade !== null)
                <p class="cijfer">Rapportcijfer {{ number_format($report->grade, 1, ',', '.') }}</p>
            @endif

            <h3>Toelichting van het testpanel</h3>
            <p class="uitleg">Sterke punten, verbeterpunten en de algemene indruk van het geteste product.</p>
            <div class="blok">
                @forelse ($toelichting as [$titel, $tekst])
                    <h4>{{ $titel }}</h4>
                    <p>{{ $tekst }}</p>
                @empty
                    <p>Het panel heeft geen aanvullende toelichting gegeven.</p>
                @endforelse
                @if ($text->course_suggestion)
                    <h4>Tip</h4>
                    <p>{{ $text->course_suggestion }}</p>
                @endif
            </div>
        </div>

        <footer class="voet">
            <div><strong>{{ config('app.name') }} &nbsp;|&nbsp; Productbeoordeling</strong><span>Dit rapport betreft uitsluitend het ingezonden en beoordeelde monster. U mag uw eigen uitslag commercieel gebruiken, mits zonder misleidende claims. De landelijke ranglijst volgt op de officiële publicatiedatum.</span></div>
            <div><strong>{{ preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')) }}</strong></div>
        </footer>
    </article>
</body>
</html>
