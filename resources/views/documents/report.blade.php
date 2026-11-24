{{-- Rapport volgens het puntenmodel, naar het aangeleverde sjabloon. Eén vel per deelnemer; zonder naam, met het deelnemersnummer. --}}
@php
    use App\Support\DutchTime;
    use App\Support\Portal\PersonalReport;

    $beeld = asset('site/images/5097bdefb5478dc6e6537a40211dbf6432a82738.png');
    $site = mb_strtoupper((string) config('marketing.site_label'));
    $telwoorden = [1 => 'Eén onderdeel', 2 => 'Twee onderdelen', 3 => 'Drie onderdelen', 4 => 'Vier onderdelen', 5 => 'Vijf onderdelen', 6 => 'Zes onderdelen', 7 => 'Zeven onderdelen', 8 => 'Acht onderdelen', 9 => 'Negen onderdelen', 10 => 'Tien onderdelen'];
    $kleuren = ['#b9770a', '#a66a08', '#8f5a06', '#774a06', '#5e3a06', '#4a2d06', '#3a2305', '#2c1a04'];
@endphp
<x-document :title="$title" :back-url="$backUrl" :back-label="$backLabel" :hint="$hint ?? null">
    <x-slot:styles>
        <style>
            .rap { padding: 8mm 12mm 6mm; }
            .rap__kop { display: flex; align-items: center; justify-content: space-between; gap: 6mm; padding-bottom: 4.5mm; border-bottom: 1px solid #ecd9a9; }
            .rap .merk__beeld { width: 14mm; height: 14mm; }
            .rap__intro { display: flex; align-items: flex-start; justify-content: space-between; gap: 8mm; margin-top: 5mm; }
            .rap__titel { margin: 2mm 0 2mm; font-size: 29pt; font-weight: 400; line-height: 1.04; }
            .rap__titel em { display: block; color: #8a5a00; font-weight: 600; }
            .rap__sub { margin: 0; font-size: 9pt; color: #6f6255; line-height: 1.45; }
            .rap__cijfer { flex: none; width: 36mm; height: 36mm; border: 2px solid #c8860a; border-radius: 50%; background: #fff1de; box-shadow: 0 8px 22px rgba(138, 90, 0, .12); display: flex; flex-direction: column; align-items: center; justify-content: center; }
            .rap__cijfer .label { font-size: 6.5pt; letter-spacing: .16em; }
            .rap__getal { min-width: 18mm; margin-top: 1mm; padding: 0 2mm .6mm; border-bottom: 1.5px solid #1c0f03; text-align: center; font-size: 27pt; font-weight: 600; line-height: 1; font-variant-numeric: lining-nums; }
            .rap__van { margin-top: 1mm; font-size: 11pt; }
            .rap__meta { display: grid; grid-template-columns: 1.15fr 1fr 1.15fr 1.25fr; gap: 5mm; margin-top: 4mm; }
            .rap__meta .label { display: block; font-size: 7pt; letter-spacing: .12em; color: #5c4d3c; }
            .rap__waarde { min-height: 7.5mm; padding: 0 0 1.2mm; display: flex; align-items: flex-end; border-bottom: 1px solid #1c0f03; font-size: 10pt; font-weight: 700; white-space: nowrap; overflow: hidden; }
            .rap__meta .label { margin-top: 1.5mm; }
            .rap__tabel { margin-top: 5mm; border: 1px solid #ece3d1; border-radius: 14px; background: #fff; overflow: hidden; }
            .rap__rij { display: grid; grid-template-columns: 1.5fr 1.6fr .9fr; align-items: center; gap: 4mm; padding: 1.35mm 6mm; border-top: 1px solid #f0e9da; font-size: 9.5pt; }
            .rap__rij--kop { border-top: 0; background: #efe6d3; padding-top: 2.6mm; padding-bottom: 2.6mm; }
            .rap__rij--kop span { font-size: 7.5pt; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
            .rap__rij--totaal { background: #fbf6ea; font-weight: 800; }
            .rap__naam { font-weight: 700; }
            .rap__gewicht { display: flex; align-items: center; gap: 2.5mm; font-size: 11.5pt; font-weight: 600; }
            .rap__staaf { height: 2.4mm; border-radius: 2mm; }
            .rap__score { justify-self: start; min-width: 19mm; padding: .5mm 3mm; border: 1px solid #edd9a8; border-radius: 8px; background: #fffaf0; text-align: center; font-size: 12pt; font-weight: 600; font-variant-numeric: lining-nums; }
            .rap__score small { font-family: 'Manrope', Arial, sans-serif; font-size: 7pt; font-weight: 600; color: #7a6b58; }
            .rap__blokken { display: grid; grid-template-columns: 1fr 1fr; gap: 5mm; margin-top: 5mm; }
            .rap__blok { padding: 4mm 5mm 4.5mm; border-radius: 14px; background: #efe6d3; }
            .rap__blok .label { font-size: 7.5pt; color: #1c0f03; letter-spacing: .12em; }
            .rap__blok p { margin: 2mm 0 0; font-size: 9pt; line-height: 1.5; white-space: pre-line; }
            .rap__extra { margin-top: 4mm; font-size: 9pt; line-height: 1.5; }
            .rap__extra .label { font-size: 7.5pt; }
            .rap__extra p { margin: .5mm 0 2mm; white-space: pre-line; }
            .rap__voet { margin-top: auto; padding-top: 5mm; }
            .rap__voet .motto { font-size: 12.5pt; }
            .rap__klein { margin: 2mm auto 0; max-width: 150mm; text-align: center; font-size: 6.5pt; line-height: 1.45; color: #7a6b58; }
            .rap__concept { margin-bottom: 3mm; padding: 1.5mm 4mm; border-radius: 8px; background: #fbe9d9; text-align: center; font-size: 7.5pt; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #8c2f0b; }
        </style>
    </x-slot:styles>

    @foreach ($reports as $report)
        @php
            $entry = $report->entry;
            $text = $report->report;
            $maximum = (int) collect($report->criteria)->sum('max');
            $grootste = max(1, (int) collect($report->criteria)->max('max'));
            $aantal = count($report->criteria);
        @endphp
        <article class="vel">
            <div class="vel__cirkel" style="width: 150mm; height: 150mm; top: -72mm; right: -52mm;"></div>
            <div class="kader"><div class="kader__binnen rap">
                @unless ($text->isPublished())
                    <div class="rap__concept">Concept · nog niet vrijgegeven aan de deelnemer</div>
                @endunless
                <header class="rap__kop">
                    <div class="merk">
                        <div class="merk__beeld"><img src="{{ $beeld }}" alt=""></div>
                        <div><div class="merk__naam kop-font">De Gouden Bol</div><div class="merk__sub">Oliebollen test</div></div>
                    </div>
                    <div class="label">&#9733; Rapport &#9733;</div>
                </header>

                <div class="rap__intro">
                    <div>
                        <div class="label">Het {{ $maximum }}-puntenmodel</div>
                        <h1 class="rap__titel kop-font">{{ $telwoorden[$aantal] ?? $aantal.' onderdelen' }}, <em>{{ $maximum === 100 ? 'honderd punten' : $maximum.' punten' }}</em></h1>
                        <p class="rap__sub">Blind beoordeeld.@if ($maximum === 100) Het totaal gedeeld door 10 is het rapportcijfer.@endif @if ($report->cardCount) Gemiddelde van {{ $report->cardCount }} geldige scorekaarten.@endif</p>
                    </div>
                    <div class="rap__cijfer">
                        <span class="label">Rapportcijfer</span>
                        <span class="rap__getal kop-font">{{ $report->grade !== null ? number_format($report->grade, 1, ',', '.') : '–' }}</span>
                        <span class="rap__van kop-font">/ 10</span>
                    </div>
                </div>

                <div class="rap__meta">
                    <div><div class="rap__waarde">{{ $entry->delivery_code ?? '–' }}</div><span class="label">Deelnemersnummer</span></div>
                    <div><div class="rap__waarde">{{ $entry->province?->name ?? '–' }}</div><span class="label">Provincie</span></div>
                    <div><div class="rap__waarde">{{ $report->roundLabel() }}</div><span class="label">Ronde</span></div>
                    <div><div class="rap__waarde">{{ $text->tested_on ? DutchTime::format($text->tested_on, 'D MMMM YYYY') : '–' }}</div><span class="label">Datum</span></div>
                </div>

                <div class="rap__tabel">
                    <div class="rap__rij rap__rij--kop"><span>Onderdeel</span><span>Gewicht</span><span>Score</span></div>
                    @foreach ($report->criteria as $index => $criterion)
                        <div class="rap__rij">
                            <span class="rap__naam">{{ $criterion['name'] }}</span>
                            <span class="rap__gewicht kop-font"><span class="rap__staaf" style="width: {{ round($criterion['max'] / $grootste * 30, 1) }}mm; background: {{ $kleuren[min($index, count($kleuren) - 1)] }};"></span>{{ $criterion['max'] }}</span>
                            <span class="rap__score kop-font">{{ PersonalReport::number($criterion['score']) }} <small>van {{ $criterion['max'] }}</small></span>
                        </div>
                    @endforeach
                    <div class="rap__rij rap__rij--totaal">
                        <span>Totaal</span>
                        <span class="rap__gewicht kop-font">{{ $maximum }}</span>
                        <span class="rap__score kop-font">{{ PersonalReport::number($report->totalPoints) }} <small>van {{ $maximum }}</small></span>
                    </div>
                </div>

                <div class="rap__blokken">
                    <div class="rap__blok"><span class="label">&#9733; Wat al goud is</span><p>{{ filled($text->strengths) ? $text->strengths : 'Het panel heeft hier geen toelichting bij gegeven.' }}</p></div>
                    <div class="rap__blok"><span class="label">&#9670; Op weg naar goud</span><p>{{ filled($text->opportunities) ? $text->opportunities : 'Het panel heeft hier geen toelichting bij gegeven.' }}</p></div>
                </div>

                @if (filled($text->general_impression) || filled($text->course_suggestion))
                    <div class="rap__extra">
                        @if (filled($text->general_impression))<span class="label">Algemene indruk</span><p>{{ $text->general_impression }}</p>@endif
                        @if (filled($text->course_suggestion))<span class="label">Tip</span><p>{{ $text->course_suggestion }}</p>@endif
                    </div>
                @endif

                <footer class="rap__voet">
                    <div class="motto kop-font">Naam eraf, nummer erop. Beoordeeld op deeg, geduld, vakmanschap en kwaliteit.</div>
                    <div class="site" style="margin-top: 2mm;">&#9733;&nbsp; {{ $site }} &nbsp;&#9733;</div>
                    <p class="rap__klein">Dit rapport betreft uitsluitend het ingezonden en beoordeelde monster. U mag uw eigen uitslag commercieel gebruiken, mits zonder misleidende claims. De landelijke ranglijst volgt op de officiële publicatiedatum.</p>
                </footer>
            </div></div>
        </article>
    @endforeach
</x-document>
