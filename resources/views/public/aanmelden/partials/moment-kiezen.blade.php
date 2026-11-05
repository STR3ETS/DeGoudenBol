{{-- Na de betaling direct een aanlevermoment of ophaalvrijdag kiezen (besluit Raphael 2 nov 2026). --}}
@php
    $location = $entry->company->primaryLocation;
    $voorkeur = $entry->pickup_preference ?? [];
@endphp
<section class="card mb-8 !p-6 sm:!p-8" aria-labelledby="moment-h">
    @if ($entry->pickup_prepaid)
        <h2 class="kop-3 mb-2" id="moment-h">Kies uw <em>ophaalvrijdag</em></h2>
        <p class="body-text mb-6 text-[0.9rem]">De voorrijkosten heeft u al betaald. Kies de vrijdag en controleer het adres; daarna ligt het ophaalmoment vast.</p>

        @if ($pickupDays->isEmpty())
            <p class="body-text text-[0.9rem]">Er zijn op dit moment geen vrijdagen te kiezen. We sturen u een herinnering zodra dat kan; u kunt het ook later in het portaal regelen.</p>
        @else
            <form method="post" action="{{ route('aanmelden.ophalen', $order) }}" class="grid gap-4">
                @csrf
                <div class="grid gap-3">
                    @foreach ($pickupDays as $day)
                        @php $vrij = max(0, $day->capacity - $day->taken_count); @endphp
                        <label class="card card--hover flex cursor-pointer items-center justify-between gap-3 !p-4 {{ $vrij === 0 ? 'opacity-50' : '' }}">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="pickup_day" value="{{ $day->getKey() }}" class="h-4 w-4 accent-goud" @checked(old('pickup_day', $voorkeur['pickup_day_id'] ?? null) == $day->getKey()) @disabled($vrij === 0) required>
                                <span class="font-semibold">{{ $day->label() }}</span>
                            </span>
                            @if ($vrij === 0)<span class="text-[0.78rem] text-gedempt">vol</span>@endif
                        </label>
                    @endforeach
                </div>
                @error('pickup_day') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field name="contact_name" label="Contactpersoon" :value="old('contact_name', $entry->company->contact_name)" :required="true" autocomplete="name" />
                    <x-ui.field name="phone" label="Telefoon" type="tel" :value="old('phone', $voorkeur['phone'] ?? $entry->company->contact_phone)" :required="true" autocomplete="tel" />
                    <x-ui.field name="postcode" label="Postcode" :value="old('postcode', $voorkeur['postcode'] ?? $location?->postcode)" :required="true" autocomplete="postal-code" />
                    <x-ui.field name="house_number" label="Huisnummer" :value="old('house_number', $voorkeur['house_number'] ?? $location?->house_number)" :required="true" />
                    <x-ui.field name="street" label="Straat" :value="old('street', $voorkeur['street'] ?? $location?->street)" :required="true" autocomplete="address-line1" />
                    <x-ui.field name="city" label="Plaats" :value="old('city', $voorkeur['city'] ?? $location?->city)" :required="true" autocomplete="address-level2" />
                </div>
                <x-ui.field name="instructions" label="Instructies voor de chauffeur" :value="old('instructions', $voorkeur['instructions'] ?? null)" placeholder="Bijvoorbeeld: achterom, bel aan bij de bakkerij" />
                <div><x-ui.button type="submit">Ophaalvrijdag vastleggen</x-ui.button></div>
            </form>
        @endif
    @else
        <h2 class="kop-3 mb-2" id="moment-h">Kies uw <em>aanlevermoment</em></h2>
        <p class="body-text mb-6 text-[0.9rem]">Wanneer brengt u uw {{ $entry->edition->settings->piecesPerEntry }} oliebollen? Kies hieronder een moment; u ontvangt direct een bevestiging met uw deelnemercode.</p>

        @if ($slots->isEmpty())
            <p class="body-text text-[0.9rem]">Er zijn op dit moment geen aanlevermomenten te kiezen. We sturen u een herinnering zodra dat kan; u kunt het ook later in het portaal regelen.</p>
        @else
            <form method="post" action="{{ route('aanmelden.moment', $order) }}" class="grid gap-4">
                @csrf
                <div class="grid gap-3">
                    @foreach ($slots as $slot)
                        @php $vrij = max(0, $slot->capacity - $slot->taken_count); @endphp
                        <label class="card card--hover flex cursor-pointer items-center justify-between gap-3 !p-4 {{ $vrij === 0 ? 'opacity-50' : '' }}">
                            <span class="flex items-start gap-3">
                                <input type="radio" name="slot" value="{{ $slot->getKey() }}" class="mt-1 h-4 w-4 accent-goud" @checked(old('slot') == $slot->getKey()) @disabled($vrij === 0) required>
                                <span>
                                    <span class="block font-semibold">{{ ucfirst($slot->label()) }}</span>
                                    @if ($slot->testLocation)
                                        <span class="block text-[0.8rem] text-gedempt">{{ $slot->testLocation->name }}, {{ $slot->testLocation->city }}</span>
                                    @endif
                                </span>
                            </span>
                            @if ($vrij === 0)<span class="whitespace-nowrap text-[0.78rem] text-gedempt">vol</span>@endif
                        </label>
                    @endforeach
                </div>
                @error('slot') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                <div><x-ui.button type="submit">Aanlevermoment vastleggen</x-ui.button></div>
            </form>
        @endif
        @if ($entry->edition->settings->pickupEnabled)
            <p class="mt-5 text-[0.82rem] text-gedempt">Liever laten ophalen op vrijdag ({{ App\Support\Money::format($entry->edition->settings->pickupFeeCents) }} voorrijkosten excl. btw)? Dat regelt u in het portaal.</p>
        @endif
    @endif
</section>
