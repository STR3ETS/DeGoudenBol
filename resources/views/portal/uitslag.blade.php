@php
    use App\Domain\Participants\Enums\EntryStatus;
    use App\Support\DutchTime;
@endphp
<x-layouts.portal title="Uitslag">
    <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-8">Uw <em>uitslag</em>{{ $edition ? ' '.$edition->year : '' }}</h1>

    @if ($entry === null)
        <x-ui.empty-state titel="Geen inschrijving voor deze editie" />
    @elseif ($item === null)
        <x-ui.card>
            <x-ui.chip status="info" :dot="true">{{ $entry->status->getLabel() }}</x-ui.chip>
            <p class="body-text mt-4 text-[0.95rem]">Zodra het panel uw oliebollen blind heeft beoordeeld en scorecontrole de uitslag definitief heeft gemaakt, verschijnt het resultaat hier in de eerstvolgende publicatiebatch{{ $edition ? ' ('.implode(' en ', array_map(fn ($d) => ['tuesday' => 'dinsdag', 'friday' => 'vrijdag', 'monday' => 'maandag', 'wednesday' => 'woensdag', 'thursday' => 'donderdag', 'saturday' => 'zaterdag', 'sunday' => 'zondag'][$d] ?? $d, $edition->settings->publicationDays)).' om '.$edition->settings->publicationTime.')' : '' }}.</p>
        </x-ui.card>
    @else
        <div class="mb-6 grid gap-6 md:grid-cols-[1fr_1fr]">
            <x-ui.card variant="zand">
                <x-ui.eyebrow>{{ $item->isPublic() ? 'Openbaar cijfer' : 'Vertrouwelijk resultaat' }}</x-ui.eyebrow>
                @if ($item->isPublic())
                    <div class="flex items-end gap-4">
                        <span class="cijfer font-kop text-[4rem] leading-none font-bold text-espresso">{{ number_format($item->total(), 1, ',', '.') }}</span>
                        <x-ui.chip status="succes" :dot="true" class="mb-2">Officieel getest</x-ui.chip>
                    </div>
                    <p class="mt-3 text-[0.9rem] text-gedempt">
                        Gepubliceerd op {{ DutchTime::format($entry->published_at, 'D MMMM YYYY, HH:mm') }} · {{ $item->result_snapshot['card_count'] ?? '–' }} geldige scorekaarten.
                    </p>
                @else
                    <p class="body-text text-[0.95rem]">Het cijfer ligt onder de publicatiedrempel van {{ number_format($edition->settings->publishThreshold, 1, ',', '.') }} en wordt niet openbaar gemaakt. Niemand buiten uw bedrijf ziet dit resultaat. Hieronder vindt u het persoonlijke verbeteradvies zodra de redactie het heeft afgerond.</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <x-ui.eyebrow>Voorlijst {{ $entry->province->name }}</x-ui.eyebrow>
                @if ($position)
                    <p class="font-kop text-[2.2rem] leading-tight font-semibold text-espresso">Op dit moment plaats {{ $position->position }}</p>
                    <p class="mt-2 text-[0.9rem] text-gedempt">
                        {{ $position->label->getLabel() }} ten opzichte van de vorige publicatie. De lijst kan bewegen tot de bevriezing op {{ DutchTime::format($edition->freeze_at, 'D MMMM') }}.
                        <a href="{{ route('provincies.show', $entry->province) }}" class="font-bold text-goud-tekst underline">Bekijk de Voorlijst</a>
                    </p>
                @else
                    <p class="text-[0.9rem] text-gedempt">Uw resultaat staat niet op de openbare Voorlijst.</p>
                @endif
            </x-ui.card>
        </div>

        @if ($finalist)
            <x-ui.card variant="espresso" class="mb-6">
                <x-ui.eyebrow class="eyebrow--licht">Landelijke finale</x-ui.eyebrow>
                @if ($finalist->status->participates())
                    <h2 class="kop-3 !text-room">{{ $finalist->originLabel() }} van {{ $entry->province->name }}: uitgenodigd voor de finale</h2>
                    <p class="mt-2 text-[0.9rem] text-room-zacht">Een nieuwe, blinde beoordeling{{ $edition?->final_test_day ? ' op '.DutchTime::date($edition->final_test_day) : '' }}; de provinciale score telt niet mee. Tot de publicatiedag is deze uitnodiging vertrouwelijk.</p>
                    @error('keuze')<p class="field-error mt-3">{{ $message }}</p>@enderror
                    <div class="mt-5 flex flex-wrap gap-3">
                        @if ($finalist->status->value === 'invited')
                            <form method="post" action="{{ route('portaal.finale.reageren', $company) }}">@csrf<input type="hidden" name="keuze" value="bevestigen"><x-ui.button type="submit">Deelname bevestigen</x-ui.button></form>
                        @else
                            <x-ui.chip status="succes" :dot="true">Deelname bevestigd</x-ui.chip>
                        @endif
                        <form method="post" action="{{ route('portaal.finale.reageren', $company) }}" onsubmit="return confirm('Afmelden voor de finale? De plek gaat naar de volgende op de lijst; uw provinciale titel blijft.');">@csrf<input type="hidden" name="keuze" value="afmelden"><x-ui.button type="submit" variant="outline-licht">Afmelden</x-ui.button></form>
                    </div>
                @else
                    <h2 class="kop-3 !text-room">Afgemeld voor de finale</h2>
                    <p class="mt-2 text-[0.9rem] text-room-zacht">De provinciale titel blijft van u.</p>
                @endif
            </x-ui.card>
        @endif

        <x-ui.card class="mb-6">
            <x-ui.eyebrow>Vertrouwelijk rapport</x-ui.eyebrow>
            @if ($report)
                <div class="grid gap-8 md:grid-cols-2">
                    <div>
                        <h2 class="kop-3 mb-3 text-[1.2rem]">Sterke punten</h2>
                        <div class="prose-dgb text-[0.95rem] whitespace-pre-line">{{ $report->strengths ?: 'Nog geen tekst.' }}</div>
                    </div>
                    <div>
                        <h2 class="kop-3 mb-3 text-[1.2rem]">Ontwikkelkansen</h2>
                        <div class="prose-dgb text-[0.95rem] whitespace-pre-line">{{ $report->opportunities ?: 'Nog geen tekst.' }}</div>
                    </div>
                </div>
                @if ($report->course_suggestion)
                    <p class="mt-6 rounded-veld bg-goud-licht p-4 text-[0.9rem]">{{ $report->course_suggestion }}</p>
                @endif
            @else
                <p class="text-[0.9rem] text-gedempt">De redactie werkt aan uw rapport met sterke punten en ontwikkelkansen. U ontvangt bericht zodra het klaarstaat.</p>
            @endif
        </x-ui.card>

        <x-ui.card>
            <x-ui.eyebrow>Bezwaar</x-ui.eyebrow>
            <p class="mb-4 text-[0.9rem] text-gedempt">Bezwaar is alleen mogelijk over de procedure, binnen drie werkdagen na publicatie{{ $objectionDeadline ? ' (tot '.DutchTime::format($objectionDeadline, 'D MMMM, HH:mm').')' : '' }}. Twee beslissers buiten het panel en de registratie beoordelen het; een hertest volgt alleen bij een erkende fout.</p>

            @foreach ($objections as $objection)
                <div class="mb-3 rounded-veld border border-rand p-4 text-[0.9rem]">
                    <div class="mb-1 flex items-center justify-between gap-3">
                        <span class="text-gedempt">{{ DutchTime::format($objection->submitted_at, 'D MMM YYYY, HH:mm') }}</span>
                        <x-ui.chip :status="$objection->status->isOpen() ? 'waarschuwing' : 'neutraal'">{{ $objection->status->getLabel() }}</x-ui.chip>
                    </div>
                    <p class="whitespace-pre-line">{{ $objection->reason }}</p>
                    @if ($objection->decision)
                        <p class="mt-2 border-t border-rand pt-2 text-gedempt"><strong>Beslissing:</strong> {{ $objection->decision }}</p>
                    @endif
                </div>
            @endforeach

            @if ($objectionDeadline && $objectionDeadline->isFuture() && $objections->filter(fn ($o) => $o->status->isOpen())->isEmpty())
                <form method="post" action="{{ route('portaal.bezwaar', $company) }}" class="flex flex-col gap-3">
                    @csrf
                    <label for="reason" class="label">Toelichting op de procedurele fout</label>
                    <textarea id="reason" name="reason" rows="4" class="input" required minlength="20">{{ old('reason') }}</textarea>
                    @error('reason')<p class="field-error">{{ $message }}</p>@enderror
                    <div><x-ui.button type="submit" variant="outline">Bezwaar indienen</x-ui.button></div>
                </form>
            @endif
        </x-ui.card>
    @endif
</x-layouts.portal>
