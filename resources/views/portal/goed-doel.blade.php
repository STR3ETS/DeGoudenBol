@php
    use App\Support\DutchTime;
    use App\Support\Money;
    $percentage = $edition?->settings->charityPercentage ?? 10;
@endphp
<x-layouts.portal title="Goed doel">
    <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-3">Uw <em>goede doel</em></h1>
    <p class="body-text mb-8 max-w-[640px]">Van uw deelnamebedrag (excl. btw) gaat {{ $percentage }}% naar het goede doel dat u voordraagt. Wordt het doel goedgekeurd, dan gaat de reservering daarheen; anders naar de regionale pot van uw provincie. Zie ook de <a href="{{ route('goede-doelen') }}" class="font-bold text-goud-tekst underline">openbare goede-doelenpagina</a>.</p>

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-ui.stat :cijfer="Money::format($reservedCents)" label="Gereserveerd uit uw deelname" />
        <x-ui.stat :cijfer="$charity ? $charity->status->getLabel() : 'Nog niet voorgedragen'" label="Status van uw doel" />
    </div>

    @if ($charity)
        <x-ui.card>
            <div class="mb-1.5 text-[0.62rem] font-bold tracking-[0.2em] text-goud-tekst uppercase">{{ $charity->province?->name ?? 'Landelijk' }}{{ $charity->categoryLabel() ? ' · '.$charity->categoryLabel() : '' }}</div>
            <h2 class="kop-3">{{ $charity->name }}</h2>
            <p class="mt-2 text-[0.9rem] text-gedempt">{{ $charity->motivation }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.chip :status="$charity->status->receivesReservations() ? 'succes' : ($charity->status->value === 'alternative' ? 'waarschuwing' : 'neutraal')" :dot="true">{{ $charity->status->getLabel() }}</x-ui.chip>
                @if ($charity->is_anbi)<x-ui.chip status="neutraal">ANBI</x-ui.chip>@endif
                <span class="text-[0.8rem] text-gedempt">Voorgedragen {{ DutchTime::date($charity->created_at) }}</span>
            </div>
            @if ($charity->status->value === 'alternative' && $charity->review_note)
                <p class="mt-4 rounded-kaart bg-zand p-4 text-[0.85rem]"><strong>Toelichting van de organisatie:</strong> {{ $charity->review_note }}</p>
            @endif
        </x-ui.card>
    @elseif ($canNominate && $edition)
        <x-ui.card>
            <h2 class="kop-3 mb-4 text-[1.2rem]">Goed doel voordragen</h2>
            <form method="post" action="{{ route('portaal.goed-doel.voordragen', $company) }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <x-ui.field name="name" label="Naam van het doel" :required="true" class="sm:col-span-2" />
                <x-ui.field name="kvk_or_rsin" label="KvK- of RSIN-nummer" />
                <x-ui.field name="website" label="Website" type="url" placeholder="https://" />
                <x-ui.select name="province_id" label="Regio (provincie)" :options="$provinces->pluck('name', 'id')->all()" placeholder="Landelijk" />
                <x-ui.select name="category" label="Categorie" :options="$categories" placeholder="Kies een categorie" />
                <div class="sm:col-span-2">
                    <label for="veld-motivation" class="label">Motivatie <span aria-hidden="true">*</span></label>
                    <textarea id="veld-motivation" name="motivation" rows="4" class="input" required>{{ old('motivation') }}</textarea>
                    @error('motivation')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-3 text-[0.9rem] sm:col-span-2"><input type="checkbox" name="is_anbi" value="1" class="h-5 w-5 accent-goud" @checked(old('is_anbi'))> Het doel heeft een ANBI-status</label>
                <div class="sm:col-span-2"><x-ui.button type="submit">Voordragen</x-ui.button></div>
            </form>
        </x-ui.card>
    @else
        <x-ui.empty-state titel="Nog geen doel voorgedragen" tekst="Alleen de eigenaar van het bedrijf kan een goed doel voordragen." />
    @endif
</x-layouts.portal>
