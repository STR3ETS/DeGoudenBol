@php use App\Support\DutchTime; @endphp
<x-layouts.panel :title="'Monster '.$assignment->sample->label()" :panelist="$panelist">
    <a href="{{ route('panel.overzicht') }}" class="mb-4 inline-block text-[0.95rem] font-bold text-goud-tekst">&larr; Mijn schema</a>

    <div class="panel-kaart mb-5 text-center">
        <x-ui.eyebrow>Testnummer</x-ui.eyebrow>
        <div class="panel-monsternummer">{{ $assignment->sample->label() }}</div>
        <p class="mt-3 text-[0.95rem] text-gedempt">
            {{ $assignment->serving_order }}e monster van uw schema
            @if ($freshUntil) · vers tot {{ DutchTime::format($freshUntil, 'HH:mm') }} @endif
        </p>
    </div>

    @if (! $sessionOpen)
        <x-ui.chip status="fout" :dot="true" class="mb-5 !text-[0.95rem]">Deze sessie is gesloten; er kunnen geen kaarten meer worden ingediend.</x-ui.chip>
    @elseif ($freshUntil?->isPast())
        <x-ui.chip status="fout" :dot="true" class="mb-5 !text-[0.95rem]">De versheid van dit monster is verlopen; scoren is geblokkeerd.</x-ui.chip>
    @endif

    @error('scores')
        <p class="field-error mb-4 !text-[0.95rem]">{{ $message }}</p>
    @enderror

    <form method="post" action="{{ route('panel.monster.indienen', $assignment) }}" class="panel-kaart !p-0" data-panel-scorecard data-assignment-id="{{ $assignment->getKey() }}" data-sample="{{ $assignment->sample->label() }}" data-overview-url="{{ route('panel.overzicht') }}">
        @csrf
        <input type="hidden" name="uuid" value="{{ $uuid }}">

        <div class="px-5 pt-2 sm:px-6">
            @foreach ($criteria as $criterion)
                <div class="panel-onderdeel">
                    <div>
                        <label for="score-{{ $criterion->code }}" class="block text-[1.1rem] font-extrabold text-espresso">{{ $criterion->name }} <span class="font-semibold text-gedempt">/ {{ $criterion->max_points }}</span></label>
                        @if ($criterion->description && ! str_starts_with($criterion->description, '['))
                            <p class="mt-1 text-[0.95rem] leading-relaxed text-gedempt">{{ $criterion->description }}</p>
                        @endif
                    </div>
                    <div class="panel-stapper">
                        <button type="button" class="panel-stap" data-stap="-1" aria-label="Eén punt minder voor {{ $criterion->name }}">&minus;</button>
                        <input id="score-{{ $criterion->code }}" name="scores[{{ $criterion->code }}]" type="number" inputmode="numeric" min="0" max="{{ $criterion->max_points }}" step="1" value="{{ old('scores.'.$criterion->code, '') }}" class="panel-invoer" data-max="{{ $criterion->max_points }}" required>
                        <button type="button" class="panel-stap" data-stap="1" aria-label="Eén punt meer voor {{ $criterion->name }}">+</button>
                    </div>
                </div>
            @endforeach

            <div class="grid gap-5 py-6 sm:grid-cols-2">
                <div>
                    <label for="strengths" class="label !text-[0.85rem]">Sterke punten</label>
                    <textarea id="strengths" name="strengths" rows="4" class="input !text-[1rem]" maxlength="2000" placeholder="Wat is er goed aan deze oliebol?">{{ old('strengths') }}</textarea>
                </div>
                <div>
                    <label for="opportunities" class="label !text-[0.85rem]">Ontwikkelkansen</label>
                    <textarea id="opportunities" name="opportunities" rows="4" class="input !text-[1rem]" maxlength="2000" placeholder="Wat kan beter?">{{ old('opportunities') }}</textarea>
                </div>
            </div>
        </div>

        <div class="panel-totaalbalk">
            <div>
                <div class="text-[0.7rem] font-bold tracking-[0.18em] text-room-meta uppercase">Lopend totaal</div>
                <div class="font-kop text-[2rem] leading-none font-bold tabular-nums"><span data-panel-totaal>0</span><span class="text-[1.1rem] text-room-meta"> / 100</span></div>
            </div>
            <button type="submit" class="panel-knop" data-panel-indienen @disabled(! $sessionOpen || $freshUntil?->isPast())>Indienen en vergrendelen</button>
        </div>
        <p class="px-5 pb-4 pt-3 text-center text-[0.85rem] text-gedempt sm:px-6" data-panel-melding>Na indienen kunt u deze kaart niet meer inzien of wijzigen.</p>
    </form>
</x-layouts.panel>
