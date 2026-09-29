@php
    use App\Support\DutchTime;
    use App\Support\Money;

    $edition = $this->edition;
    $stepLabels = ['Bedrijf', 'Adres', 'Publicatie', 'Pakket', 'Afronden'];
@endphp
<div class="site-container pt-36 pb-24">
    @if (! $this->isOpen)
        <div class="mx-auto max-w-[640px] text-center">
            <x-ui.eyebrow>Aanmelden</x-ui.eyebrow>
            <h1 class="kop-pagina mb-5">De inschrijving is <em>nog niet open</em></h1>
            <p class="body-text mb-8">
                @if ($edition?->registration_opens_at)
                    De inschrijving voor De Gouden Bol {{ $edition->year }} opent op {{ DutchTime::format($edition->registration_opens_at, 'dddd D MMMM YYYY') }}.
                @else
                    De inschrijving voor de volgende editie wordt binnenkort aangekondigd.
                @endif
            </p>
            <x-ui.button :href="route('home')" variant="outline">Terug naar de homepage</x-ui.button>
        </div>
    @else
        <div class="mx-auto max-w-[760px]">
            <x-ui.eyebrow>Aanmelden voor editie {{ $edition->year }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-8">Laat uw oliebollen <em>officieel keuren</em></h1>

            {{-- Stapindicator --}}
            <ol class="mb-10 flex flex-wrap gap-2" aria-label="Stappen">
                @foreach ($stepLabels as $index => $label)
                    @php $number = $index + 1; @endphp
                    <li>
                        <button
                            type="button"
                            wire:click="goTo({{ $number }})"
                            class="chip {{ $number === $step ? 'chip--solid' : ($number < $step ? 'chip--succes' : '') }}"
                            @if ($number >= $step) disabled @endif
                            @if ($number === $step) aria-current="step" @endif
                        >
                            <span class="font-kop text-[0.95rem]">{{ $number }}</span> {{ $label }}
                        </button>
                    </li>
                @endforeach
            </ol>

            @error('step')
                <p class="field-error mb-6">{{ $message }}</p>
            @enderror

            <x-ui.card :paneel="true" class="!p-7 md:!p-12">
                {{-- Stap 1: bedrijf --}}
                @if ($step === 1)
                    <h2 class="kop-3 mb-2">Uw bedrijf</h2>
                    <p class="body-text mb-7 text-[0.9rem]">De gegevens van de productielocatie. Verkooppunten met hetzelfde product vallen onder deze inschrijving.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field name="companyName" label="Bedrijfsnaam" wire:model.blur="companyName" :required="true" placeholder="Bakkerij…" autocomplete="organization" />
                        <x-ui.select name="companyType" label="Type bedrijf" wire:model="companyType" :required="true" :options="$this->companyTypeOptions()" />
                        <x-ui.field name="kvkNumber" label="KvK-nummer" wire:model.blur="kvkNumber" placeholder="12345678" inputmode="numeric" help="Acht cijfers. Eén inschrijving per productielocatie." />
                        <x-ui.field name="website" label="Website" wire:model.blur="website" type="url" placeholder="https://" />
                        <x-ui.field name="contactName" label="Contactpersoon" wire:model.blur="contactName" :required="true" autocomplete="name" />
                        <x-ui.field name="email" label="E-mailadres" wire:model.blur="email" type="email" :required="true" autocomplete="email" help="Hierop ontvangt u de bevestiging en de inloglink voor het portaal." />
                        <x-ui.field name="phone" label="Telefoon" wire:model.blur="phone" type="tel" autocomplete="tel" />
                    </div>
                @endif

                {{-- Stap 2: adres --}}
                @if ($step === 2)
                    <h2 class="kop-3 mb-2">Adres en provincie</h2>
                    <p class="body-text mb-7 text-[0.9rem]">Vul postcode en huisnummer in; straat, plaats en provincie worden automatisch aangevuld.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field name="postcode" label="Postcode" wire:model.blur="postcode" :required="true" placeholder="1234 AB" autocomplete="postal-code" />
                        <x-ui.field name="houseNumber" label="Huisnummer" wire:model.blur="houseNumber" :required="true" placeholder="12a" />
                        <x-ui.field name="street" label="Straat" wire:model.blur="street" :required="true" autocomplete="address-line1" />
                        <x-ui.field name="city" label="Plaats" wire:model.blur="city" :required="true" autocomplete="address-level2" />
                    </div>
                    @if ($lookupFailed)
                        <p class="field-help mt-3">Het adres kon niet automatisch worden gevonden. Vul straat, plaats en provincie zelf in.</p>
                    @endif
                    <div class="mt-4">
                        <x-ui.select name="provinceId" label="Provincie" wire:model.live="provinceId" :required="true" :options="$this->provinces->pluck('name', 'id')->all()" />
                        @if ($this->selectedProvince)
                            @php $availability = $this->availability[$this->selectedProvince->slug] ?? null; @endphp
                            @if ($availability)
                                <p class="field-help mt-2">
                                    @if ($availability['available'] > 0)
                                        Nog {{ $availability['available'] }} van {{ $availability['capacity'] }} plekken beschikbaar in {{ $this->selectedProvince->name }}.
                                    @else
                                        Alle {{ $availability['capacity'] }} plekken in {{ $this->selectedProvince->name }} zijn bezet.
                                    @endif
                                </p>
                            @endif
                        @endif
                    </div>
                @endif

                {{-- Stap 3: publicatie --}}
                @if ($step === 3)
                    <h2 class="kop-3 mb-2">Zo staat u op de site</h2>
                    <p class="body-text mb-7 text-[0.9rem]">Deze gegevens verschijnen op uw profiel en, vanaf een cijfer van {{ number_format($edition->settings->publishThreshold, 1, ',', '.') }}, op de Voorlijst. Verhaal en foto's voegt u straks toe in het portaal.</p>
                    <div class="grid gap-4">
                        <x-ui.field name="publicName" label="Naam op de site" wire:model.blur="publicName" :required="true" />
                        <x-ui.field name="tagline" label="Korte omschrijving" wire:model.blur="tagline" placeholder="Bijvoorbeeld: familiebedrijf sinds 1948" help="Maximaal 140 tekens." />
                    </div>

                    <fieldset class="mt-6">
                        <legend class="label">Allergenen in uw oliebol</legend>
                        <p class="field-help mb-3 mt-0">Verplicht voor het panel. Vink aan wat in het product zit.</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($this->allergenOptions() as $value => $label)
                                <label class="checklist-rij cursor-pointer !py-2.5 text-[0.85rem]">
                                    <input type="checkbox" wire:model="allergens" value="{{ $value }}" class="h-4 w-4 accent-goud">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('allergens.*') <p class="field-error">{{ $message }}</p> @enderror
                    </fieldset>

                    <div class="mt-6">
                        <label for="veld-productNotes" class="label">Toelichting voor de organisatie (optioneel)</label>
                        <textarea id="veld-productNotes" wire:model.blur="productNotes" class="input min-h-[110px]" maxlength="1000" placeholder="Bijvoorbeeld bijzonderheden over het product of het aanleveren"></textarea>
                        @error('productNotes') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Stap 4: pakket --}}
                @if ($step === 4)
                    <h2 class="kop-3 mb-2">Deelname</h2>
                    <p class="body-text mb-7 text-[0.9rem]">Erkenningen en badges hangen nooit aan een pakket; die volgen alleen uit de beoordeling.</p>
                    <div class="grid gap-4">
                        @foreach ($this->packages as $package)
                            <label class="card card--hover block cursor-pointer !p-6 {{ $packageId === $package->getKey() ? 'ring-2 ring-goud' : '' }}">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-3">
                                        <input type="radio" wire:model.live="packageId" value="{{ $package->getKey() }}" class="mt-1 h-4 w-4 accent-goud">
                                        <div>
                                            <div class="font-kop text-[1.25rem] font-semibold">{{ $package->name }}</div>
                                            @if ($package->description)
                                                <p class="body-text mt-1 text-[0.85rem]">{{ $package->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="cijfer text-[1.6rem] text-goud-tekst">{{ $package->formattedPrice() }}</div>
                                        <div class="text-[0.72rem] text-gedempt">excl. btw · {{ $package->formattedPriceInclVat() }} incl.</div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('packageId') <p class="field-error">{{ $message }}</p> @enderror
                @endif

                {{-- Stap 5: overzicht en voorwaarden --}}
                @if ($step === 5)
                    <h2 class="kop-3 mb-6">Controleren en afronden</h2>
                    <dl class="mb-8 grid gap-x-8 gap-y-3 text-[0.9rem] sm:grid-cols-[160px_1fr]">
                        <dt class="text-gedempt">Bedrijf</dt><dd class="font-semibold">{{ $companyName }} <span class="font-normal text-gedempt">({{ $this->companyTypeOptions()[$companyType] ?? $companyType }})</span></dd>
                        <dt class="text-gedempt">Contact</dt><dd>{{ $contactName }} · {{ $email }}@if ($phone) · {{ $phone }}@endif</dd>
                        <dt class="text-gedempt">Adres</dt><dd>{{ $street }} {{ $houseNumber }}, {{ $postcode }} {{ $city }}</dd>
                        <dt class="text-gedempt">Provincie</dt><dd>{{ $this->selectedProvince?->name }}</dd>
                        <dt class="text-gedempt">Op de site</dt><dd>{{ $publicName }}@if ($tagline) <span class="text-gedempt">· {{ $tagline }}</span>@endif</dd>
                        <dt class="text-gedempt">Allergenen</dt><dd>{{ $allergens ? collect($allergens)->map(fn ($a) => $this->allergenOptions()[$a] ?? $a)->join(', ') : 'Geen opgegeven' }}</dd>
                        <dt class="text-gedempt">Pakket</dt>
                        <dd>
                            @if ($this->selectedPackage)
                                {{ $this->selectedPackage->name }} · {{ $this->selectedPackage->formattedPrice() }} excl. btw
                                <span class="text-gedempt">({{ $this->selectedPackage->formattedPriceInclVat() }} incl. {{ rtrim(rtrim(number_format($this->selectedPackage->vat_rate, 2, ',', '.'), '0'), ',') }}% btw)</span>
                            @endif
                        </dd>
                    </dl>

                    <div class="flex flex-col gap-3">
                        <label class="checklist-rij cursor-pointer items-start text-[0.85rem]">
                            <input type="checkbox" wire:model="acceptTerms" class="mt-0.5 h-4 w-4 accent-goud">
                            <span>Ik ga akkoord met de <a href="{{ Route::has('voorwaarden') ? route('voorwaarden') : '#' }}" class="text-goud-tekst underline" target="_blank" rel="noopener">deelnamevoorwaarden</a> (versie {{ $this->terms?->version }}), inclusief het blind protocol en de publicatie van het cijfer vanaf {{ number_format($edition->settings->publishThreshold, 1, ',', '.') }}.</span>
                        </label>
                        @error('acceptTerms') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                        <label class="checklist-rij cursor-pointer items-start text-[0.85rem]">
                            <input type="checkbox" wire:model="acceptPrivacy" class="mt-0.5 h-4 w-4 accent-goud">
                            <span>Ik heb de <a href="{{ Route::has('privacy') ? route('privacy') : '#' }}" class="text-goud-tekst underline" target="_blank" rel="noopener">privacyverklaring</a> gelezen.</span>
                        </label>
                        @error('acceptPrivacy') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                        @error('provinceId') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                        @error('kvkNumber') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                        @if ($turnstileSiteKey)
                            <div wire:ignore class="mt-2">
                                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-callback="dgbTurnstileVerified" data-theme="light" data-language="nl"></div>
                            </div>
                            @error('turnstileToken') <p class="field-error !mt-0">{{ $message }}</p> @enderror
                            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                            <script>window.dgbTurnstileVerified = (token) => { @this.set('turnstileToken', token, false); };</script>
                        @endif
                    </div>
                @endif

                {{-- Navigatie --}}
                <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-rand pt-6">
                    <div>
                        @if ($step > 1)
                            <x-ui.button variant="ghost" wire:click="back" type="button">Vorige</x-ui.button>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <span wire:loading class="text-[0.75rem] text-gedempt">Even geduld…</span>
                        @if ($step < self::STEPS)
                            <x-ui.button wire:click="next" type="button">Volgende</x-ui.button>
                        @else
                            <x-ui.button wire:click="submit" type="button" wire:loading.attr="disabled">
                                Naar betaling · {{ $this->selectedPackage ? $this->selectedPackage->formattedPriceInclVat() : '' }}
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            </x-ui.card>

            <p class="mt-4 text-center text-[0.75rem] text-gedempt">Uw plek wordt {{ config('commerce.reservation_minutes') }} minuten vastgehouden tot de betaling is afgerond.</p>
        </div>
    @endif
</div>
