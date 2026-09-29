<x-layouts.panel title="Belangenconflicten" :panelist="$panelist">
    <x-ui.eyebrow>Blind protocol</x-ui.eyebrow>
    <h1 class="kop-pagina mb-2 !text-[2.4rem]">Belangen<em>conflicten</em></h1>
    <p class="mb-6 text-[1rem] leading-relaxed text-gedempt">Kent u een deelnemende bakker persoonlijk, werkt u er of heeft u er een zakelijk belang bij? Meld het hier. U krijgt dat monster dan niet uitgeserveerd; het schema vermeldt alleen een reden-code, nooit de naam.</p>

    <div class="panel-kaart mb-6">
        <h2 class="mb-3 text-[1.1rem] font-extrabold">Gemelde bedrijven</h2>
        @if ($conflicts->isEmpty())
            <p class="text-[1rem] text-gedempt">Nog geen meldingen.</p>
        @else
            <ul class="flex flex-col gap-2">
                @foreach ($conflicts as $conflict)
                    <li class="flex items-center justify-between gap-3 rounded-veld bg-zand px-4 py-3">
                        <span class="font-bold">{{ $conflict->company->name }}</span>
                        <form method="post" action="{{ route('panel.conflicten.verwijderen', $conflict) }}">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-[0.9rem] font-bold text-gedempt underline">Intrekken</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="panel-kaart">
        <h2 class="mb-3 text-[1.1rem] font-extrabold">Bedrijf zoeken</h2>
        <form method="get" action="{{ route('panel.conflicten') }}" class="mb-4 flex gap-2">
            <input type="search" name="q" value="{{ $term }}" class="input !text-[1rem]" placeholder="Naam van de bakker" aria-label="Zoek een deelnemer">
            <button type="submit" class="panel-knop !min-h-0 shrink-0 !px-5">Zoeken</button>
        </form>

        @if ($term !== '')
            @forelse ($candidates as $entry)
                <div class="flex items-center justify-between gap-3 border-t border-rand py-3">
                    <div>
                        <div class="font-bold">{{ $entry->public_name }}</div>
                        <div class="text-[0.9rem] text-gedempt">{{ $entry->company->primaryLocation?->city }} · {{ $entry->province->name }}</div>
                    </div>
                    @if (in_array($entry->company_id, $conflictCompanyIds, true))
                        <x-ui.chip status="neutraal" class="!text-[0.85rem]">Gemeld</x-ui.chip>
                    @else
                        <form method="post" action="{{ route('panel.conflicten.toevoegen') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $entry->company_id }}">
                            <button type="submit" class="rounded-pil bg-espresso px-4 py-2 text-[0.9rem] font-bold text-room">Melden</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-[1rem] text-gedempt">Geen deelnemer gevonden met "{{ $term }}".</p>
            @endforelse
        @endif
    </div>
</x-layouts.panel>
