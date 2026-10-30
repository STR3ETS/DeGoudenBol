@php
    use App\Domain\Intake\Enums\PickupStatus;
    use App\Domain\Participants\Enums\DeliveryMethod;
    use App\Domain\Participants\Enums\EntryStatus;
    use App\Support\DutchTime;
    use App\Support\Money;

    $settings = $edition?->settings;
    $pickupPaid = $pickup?->isPaid() ?? false;
    $pickupOpen = $pickup && $pickup->status === PickupStatus::Requested;
    $pickupChangeable = $pickup && $pickup->status->isChangeable() && ! $pickup->day->changeDeadline($deadlineHours)->isPast();
    $fee = $settings ? Money::format($settings->pickupFeeCents) : null;
@endphp
<x-layouts.portal title="Aanleveren">
    <header class="ui-app__kop">
        <div class="ui-app__kop-tekst">
            <h1 class="ui-app__titel">Zelf brengen of <em>laten ophalen</em></h1>
            <p class="ui-app__sub">{{ $company->name }}@if ($settings) · {{ $settings->piecesPerEntry }} oliebollen in een doos of zak, zonder naam of logo @endif</p>
        </div>
    </header>

    @if ($errors->any())
        <div class="ui-statusregel ui-statusregel--fout ui-app__blok" role="alert"><span class="ui-icoonvak ui-icoonvak--fout ui-icoonvak--rond"><x-ui.icoon naam="waarschuwing" dikte="2.4" /></span><div>{{ $errors->first() }}</div></div>
    @endif

    @if ($entry === null)
        <div class="ui-kaart"><div class="ui-kaart__inhoud"><div class="ui-klaar !bg-zand"><div class="ui-klaar__titel">Geen inschrijving voor deze editie</div><p class="ui-klaar__tekst">Meld je eerst aan; daarna kies je hier hoe je oliebollen bij ons komen.</p></div></div></div>
    @else
        {{-- Wat er nu vastligt --}}
        @if ($entry->deliverySlot && $entry->delivery_method !== DeliveryMethod::Pickup)
            <div class="ui-kaart ui-kaart--zand ui-app__blok">
                <div class="ui-kaart__kop">
                    <span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="kalender" /></span>
                    <div><div class="ui-kaart__titel">Je brengt ze zelf</div><div class="ui-kaart__sub">{{ ucfirst(DutchTime::format($entry->deliverySlot->starts_at, 'dddd D MMMM, HH:mm')) }} – {{ DutchTime::format($entry->deliverySlot->ends_at, 'HH:mm') }}</div></div>
                    <div class="ui-kaart__acties"><a href="{{ route('portaal.planning.bewijs', $company) }}" class="ui-knop ui-knop--klein">Aanleverbewijs <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a></div>
                </div>
                <div class="ui-kaart__inhoud !pt-2 text-[0.9rem]">
                    @if ($entry->deliverySlot->testLocation)
                        <p>Aanleveradres: <strong>{{ $entry->deliverySlot->testLocation->name }}</strong>, {{ $entry->deliverySlot->testLocation->street }}, {{ $entry->deliverySlot->testLocation->postcode }} {{ $entry->deliverySlot->testLocation->city }}</p>
                    @endif
                    <p class="mt-2">Je deelnemercode: <strong class="font-kop text-[1.3rem] tracking-[0.12em]">{{ $entry->delivery_code }}</strong></p>
                </div>
            </div>
        @endif

        @if ($pickup)
            <div class="ui-kaart {{ $pickupPaid ? 'ui-kaart--zand' : '' }} ui-app__blok">
                <div class="ui-kaart__kop">
                    <span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="vlag" /></span>
                    <div><div class="ui-kaart__titel">Ophaalservice op {{ $pickup->day->label() }}</div><div class="ui-kaart__sub">{{ $pickup->address() }}</div></div>
                    <div class="ui-kaart__acties"><span class="ui-chip ui-chip--klein ui-chip--{{ $pickupPaid ? 'succes' : 'waarschuwing' }}">{{ $pickup->status->getLabel() }}</span></div>
                </div>
                <div class="ui-kaart__inhoud !pt-2 text-[0.9rem]">
                    @if ($pickupOpen)
                        <p class="mb-4">Je aanvraag staat klaar. De ophaalservice is pas actief na betaling van de voorrijkosten ({{ $pickup->order?->formattedTotal() }} incl. btw).</p>
                        <form method="post" action="{{ route('portaal.ophalen.betalen', $company) }}">@csrf<button type="submit" class="ui-knop">Voorrijkosten betalen <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></button></form>
                    @else
                        <p>Contact op de dag: {{ $pickup->contact_name }} · {{ $pickup->phone }}@if ($pickup->driver_name) · chauffeur: {{ $pickup->driver_name }}@endif</p>
                        <p class="mt-2">Je deelnemercode: <strong class="font-kop text-[1.3rem] tracking-[0.12em]">{{ $entry->delivery_code }}</strong> · <a href="{{ route('portaal.planning.bewijs', $company) }}" class="font-bold text-goud-tekst underline">aanleverbewijs</a></p>
                        <p class="mt-2 text-gedempt">{{ $pickupChangeable ? 'Een andere vrijdag kiezen kan tot '.DutchTime::format($pickup->day->changeDeadline($deadlineHours), 'dddd D MMMM [om] HH:mm').'.' : 'Wijzigen kan niet meer. Neem contact met ons op als er iets verandert.' }}</p>
                    @endif
                </div>
            </div>
        @endif

        @if ($entry->status === EntryStatus::FreshnessExpired)
            <div class="ui-statusregel ui-statusregel--waarschuwing ui-app__blok"><span class="ui-icoonvak ui-icoonvak--waarschuwing ui-icoonvak--rond"><x-ui.icoon naam="klok" dikte="2.4" /></span><div><strong>Je vorige oliebollen waren te laat voor de proeverij.</strong> Kies een nieuw moment.</div></div>
        @endif

        @if (! $canChoose)
            @if (! in_array($entry->status, [EntryStatus::PendingPayment], true))
                <div class="ui-statusregel ui-statusregel--info ui-app__blok"><span class="ui-icoonvak ui-icoonvak--info ui-icoonvak--rond"><x-ui.icoon naam="info" dikte="2.4" /></span><div>Je oliebollen zijn ontvangen. Het aanlevermoment kan niet meer worden gewijzigd.</div></div>
            @else
                <div class="ui-statusregel ui-statusregel--waarschuwing ui-app__blok"><span class="ui-icoonvak ui-icoonvak--waarschuwing ui-icoonvak--rond"><x-ui.icoon naam="klok" dikte="2.4" /></span><div>Rond eerst je aanmelding af. Daarna kies je hier hoe je oliebollen bij ons komen. <a href="{{ route('portaal.aanmelding', $company) }}" class="font-bold underline">Naar je aanmelding</a></div></div>
            @endif
        @elseif (! $pickupPaid || $pickupChangeable)
            <div class="ui-app__blok grid gap-6 xl:grid-cols-2">
                {{-- Zelf brengen --}}
                @if (! $pickupPaid)
                    <section class="ui-kaart">
                        <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="kalender" /></span><div><div class="ui-kaart__titel">Zelf brengen</div><div class="ui-kaart__sub">Kies een dag en tijd. Gratis.</div></div></div>
                        <div class="ui-kaart__inhoud !pt-2">
                            @if ($slots->isEmpty())
                                <p class="text-[0.9rem] text-gedempt">De tijdslots staan nog niet klaar. Je krijgt bericht zodra je kunt kiezen.</p>
                            @else
                                <form method="post" action="{{ route('portaal.planning.kiezen', $company) }}">
                                    @csrf
                                    <ul class="ui-lijst">
                                        @foreach ($slots as $slot)
                                            @php
                                                $available = max(0, $slot->capacity - $slot->taken_count);
                                                $isCurrent = $entry->delivery_slot_id === $slot->getKey();
                                                $disabled = ! $isCurrent && ($available === 0 || ! $canChange || $slot->starts_at->subHours($deadlineHours)->isPast());
                                            @endphp
                                            <li>
                                                <label class="ui-rij {{ $isCurrent ? 'ui-rij--goud' : 'ui-rij--klik' }} {{ $disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}">
                                                    <input type="radio" name="slot" value="{{ $slot->getKey() }}" class="h-5 w-5 accent-goud" @checked($isCurrent) @disabled($disabled)>
                                                    <span class="ui-rij__tekst">
                                                        <span class="ui-rij__titel">{{ ucfirst(DutchTime::format($slot->starts_at, 'dddd D MMMM')) }}, {{ DutchTime::format($slot->starts_at, 'HH:mm') }} – {{ DutchTime::format($slot->ends_at, 'HH:mm') }}</span>
                                                        @if ($slot->testLocation)<span class="ui-rij__sub">{{ $slot->testLocation->name }}, {{ $slot->testLocation->city }}</span>@endif
                                                    </span>
                                                    <span class="ui-rij__meta">{{ $isCurrent ? 'Jouw keuze' : ($available === 0 ? 'Vol' : $available.' vrij') }}</span>
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if ($canChange)
                                        <div class="mt-4 flex flex-wrap items-center gap-3">
                                            <button type="submit" class="ui-knop">{{ $entry->deliverySlot ? 'Moment wijzigen' : 'Dit moment kiezen' }}</button>
                                            <span class="text-[0.8rem] text-gedempt">Wijzigen kan tot {{ $deadlineHours }} uur vooraf.</span>
                                        </div>
                                    @endif
                                </form>
                            @endif
                        </div>
                    </section>
                @endif

                {{-- Laten ophalen --}}
                @if ($settings?->pickupEnabled)
                    <section class="ui-kaart">
                        <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="vlag" /></span><div><div class="ui-kaart__titel">Laten ophalen</div><div class="ui-kaart__sub">Alleen op vrijdag · {{ $fee }} voorrijkosten excl. btw</div></div></div>
                        <div class="ui-kaart__inhoud !pt-2">
                            @if ($pickupDays->isEmpty())
                                <p class="text-[0.9rem] text-gedempt">Er zijn op dit moment geen vrijdagen te boeken. Kies een tijdslot om zelf te brengen, of kijk later nog eens.</p>
                            @else
                                <p class="mb-4 text-[0.88rem] text-gedempt">Een teamlid van De Gouden Bol haalt je oliebollen op. Na de betaling is het ophaalmoment vast; je krijgt een bevestiging en later de route.</p>
                                <form method="post" action="{{ route('portaal.ophalen', $company) }}" class="grid gap-4">
                                    @csrf
                                    <div class="ui-veld">
                                        <label class="ui-label" for="pickup_day">Vrijdag</label>
                                        <select id="pickup_day" name="pickup_day" class="ui-invoer ui-invoer--select" required>
                                            <option value="">Kies een vrijdag</option>
                                            @foreach ($pickupDays as $day)
                                                @php $vrij = max(0, $day->capacity - $day->taken_count); $huidig = $pickup?->pickup_day_id === $day->getKey(); @endphp
                                                <option value="{{ $day->getKey() }}" @selected(old('pickup_day', $pickup?->pickup_day_id) == $day->getKey()) @disabled($vrij === 0 && ! $huidig)>{{ $day->label() }} · {{ $huidig ? 'jouw keuze' : ($vrij === 0 ? 'vol' : $vrij.' plekken vrij') }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="ui-veld"><label class="ui-label" for="company_name">Bedrijf</label><input id="company_name" name="company_name" class="ui-invoer" value="{{ old('company_name', $pickupDefaults['company_name']) }}" required></div>
                                        <div class="ui-veld"><label class="ui-label" for="contact_name">Contactpersoon</label><input id="contact_name" name="contact_name" class="ui-invoer" value="{{ old('contact_name', $pickupDefaults['contact_name']) }}" required autocomplete="name"></div>
                                        <div class="ui-veld"><label class="ui-label" for="phone">Telefoon</label><input id="phone" name="phone" type="tel" class="ui-invoer" value="{{ old('phone', $pickupDefaults['phone']) }}" required autocomplete="tel"></div>
                                        <div class="ui-veld"><label class="ui-label" for="postcode">Postcode</label><input id="postcode" name="postcode" class="ui-invoer" value="{{ old('postcode', $pickupDefaults['postcode']) }}" required autocomplete="postal-code"></div>
                                        <div class="ui-veld"><label class="ui-label" for="street">Straat</label><input id="street" name="street" class="ui-invoer" value="{{ old('street', $pickupDefaults['street']) }}" required autocomplete="address-line1"></div>
                                        <div class="ui-veld"><label class="ui-label" for="house_number">Huisnummer</label><input id="house_number" name="house_number" class="ui-invoer" value="{{ old('house_number', $pickupDefaults['house_number']) }}" required></div>
                                        <div class="ui-veld sm:col-span-2"><label class="ui-label" for="city">Plaats</label><input id="city" name="city" class="ui-invoer" value="{{ old('city', $pickupDefaults['city']) }}" required autocomplete="address-level2"></div>
                                        <div class="ui-veld sm:col-span-2"><label class="ui-label" for="instructions">Instructies voor de chauffeur <span class="ui-label__optioneel">optioneel</span></label><textarea id="instructions" name="instructions" rows="3" class="ui-invoer" placeholder="Bijvoorbeeld: achterom, bel aan bij de bakkerij">{{ old('instructions', $pickupDefaults['instructions']) }}</textarea></div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <button type="submit" class="ui-knop">{{ $pickupPaid ? 'Vrijdag wijzigen' : ($pickupOpen ? 'Opslaan en betalen' : 'Aanvragen en betalen') }} <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></button>
                                        @unless ($pickupPaid)<span class="text-[0.8rem] text-gedempt">{{ $fee }} excl. btw, betalen via iDEAL of kaart.</span>@endunless
                                    </div>
                                </form>
                            @endif
                        </div>
                    </section>
                @endif
            </div>
        @endif
    @endif
</x-layouts.portal>
