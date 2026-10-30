@php
    use App\Domain\Participants\Enums\DiscountStatus;
    use App\Support\DutchTime;
    use App\Support\Money;

    $order = $entry?->order;
    $invoice = $order?->invoice;
    $settings = $edition?->settings;
    $base = $order?->lines->first();
    $betaalstatus = match (true) {
        $order === null => 'Geen order',
        $order->isPaid() => 'Betaald op '.DutchTime::format($order->paid_at, 'D MMMM YYYY'),
        $entry->isReservationExpired() => 'Verlopen',
        $entry->wantsDiscount() && $entry->discount_status?->blocksPayment() => 'Nog niet: eerst de kortingscontrole',
        default => 'Open: '.$order->formattedTotal().' incl. btw',
    };
@endphp
<x-layouts.portal title="Mijn aanmelding">
    <header class="ui-app__kop">
        <div class="ui-app__kop-tekst">
            <h1 class="ui-app__titel">Aangemeld en <em>betaald</em></h1>
            <p class="ui-app__sub">{{ $company->name }}{{ $edition ? ' · De Gouden Bol '.$edition->year : '' }}</p>
        </div>
    </header>

    @if ($errors->any())
        <div class="ui-statusregel ui-statusregel--fout ui-app__blok" role="alert"><span class="ui-icoonvak ui-icoonvak--fout ui-icoonvak--rond"><x-ui.icoon naam="waarschuwing" dikte="2.4" /></span><div>{{ $errors->first() }}</div></div>
    @endif

    @if ($entry === null)
        <div class="ui-kaart"><div class="ui-kaart__inhoud"><div class="ui-klaar !bg-zand"><div class="ui-klaar__titel">Geen inschrijving voor deze editie</div><p class="ui-klaar__tekst">Meld je bedrijf aan; daarna zie je hier je tarief, betaling en factuur.</p>@if (Route::has('aanmelden'))<a href="{{ route('aanmelden') }}" class="ui-knop mt-3">Aanmelden</a>@endif</div></div></div>
    @else
        <div class="ui-app__blok grid gap-6 xl:grid-cols-[3fr_2fr]">
            <section class="ui-kaart">
                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="klembord" /></span><div><div class="ui-kaart__titel">Je aanmelding</div><div class="ui-kaart__sub">{{ $entry->public_name }} · {{ $entry->province->name }}</div></div><div class="ui-kaart__acties"><span class="ui-chip ui-chip--klein ui-chip--{{ $order?->isPaid() ? 'succes' : 'waarschuwing' }}">{{ $entry->status->getLabel() }}</span></div></div>
                <div class="ui-kaart__inhoud !pt-2">
                    <dl class="grid gap-x-6 gap-y-3 text-[0.92rem] sm:grid-cols-[170px_1fr]">
                        <dt class="text-gedempt">Aangemeld op</dt><dd class="font-semibold">{{ DutchTime::format($entry->registered_at, 'D MMMM YYYY') }}</dd>
                        <dt class="text-gedempt">Tarief</dt>
                        <dd class="font-semibold">
                            {{ $entry->rate->getLabel() }}
                            @if ($base)
                                <span class="font-normal text-gedempt">· {{ match (true) {
                                    $entry->wantsDiscount() && $settings !== null => Money::format($settings->discountedCents($base->unit_price_cents)),
                                    $entry->rate === App\Domain\Participants\Enums\EntryRate::OpeningOffer => Money::format((int) $order->lines->where('lineable_type', '!=', App\Domain\Commerce\Actions\CreateOrderForEntry::PLAIN_ADDON_LINE_TYPE)->sum('subtotal_cents')),
                                    default => Money::format($base->unit_price_cents),
                                } }} excl. btw</span>
                            @endif
                        </dd>
                        @if ($entry->plain_addon)
                            <dt class="text-gedempt">Extra</dt><dd class="font-semibold">Test naturel oliebol</dd>
                        @endif
                        @if ($entry->wantsDiscount())
                            <dt class="text-gedempt">Kortingsactie</dt>
                            <dd><span class="ui-chip ui-chip--klein ui-chip--{{ match ($entry->discount_status) { DiscountStatus::Approved => 'succes', DiscountStatus::Rejected => 'fout', DiscountStatus::Submitted => 'waarschuwing', default => 'neutraal' } }}">{{ $entry->discount_status?->getLabel() }}</span>@if ($entry->discount_proof_url) <a href="{{ $entry->discount_proof_url }}" class="ml-2 text-[0.85rem] text-goud-tekst underline" target="_blank" rel="noopener">jouw post</a>@endif</dd>
                        @endif
                        <dt class="text-gedempt">Betaling</dt><dd class="font-semibold">{{ $betaalstatus }}</dd>
                        <dt class="text-gedempt">Factuur</dt>
                        <dd>
                            @if ($invoice)
                                <a href="{{ route('portaal.facturen.toon', $invoice) }}" class="font-semibold text-goud-tekst underline">{{ $invoice->number }}</a> <span class="text-gedempt">· {{ $invoice->formattedTotal() }} · bekijken of opslaan als pdf</span>
                            @else
                                <span class="text-gedempt">Volgt na de betaling.</span>
                            @endif
                        </dd>
                        @if ($entry->pickupRequest?->order?->invoice)
                            <dt class="text-gedempt">Ophaalservice</dt>
                            <dd><a href="{{ route('portaal.facturen.toon', $entry->pickupRequest->order->invoice) }}" class="font-semibold text-goud-tekst underline">{{ $entry->pickupRequest->order->invoice->number }}</a> <span class="text-gedempt">· {{ $entry->pickupRequest->order->invoice->formattedTotal() }}</span></dd>
                        @endif
                    </dl>

                    @if ($order && $order->isPending() && $entry->canPay())
                        <div class="mt-6 flex flex-wrap gap-3">
                            <form method="post" action="{{ route('aanmelden.opnieuw', $order) }}">@csrf<button type="submit" class="ui-knop">Nu betalen · {{ $order->formattedTotal() }} <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></button></form>
                        </div>
                    @elseif ($order && $order->isPending() && $entry->wantsDiscount() && $entry->discount_status?->blocksPayment() && ! $entry->isReservationExpired())
                        <form method="post" action="{{ route('aanmelden.standaardprijs', $order) }}" class="mt-6">@csrf<button type="submit" class="ui-knop ui-knop--ghost">Toch direct betalen tegen de standaardprijs</button></form>
                    @endif
                </div>
            </section>

            <div class="flex flex-col gap-6">
                @if ($entry->canSubmitDiscountProof() && $canDecide)
                    <section class="ui-kaart ui-kaart--zand" id="korting">
                        <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="bliksem" /></span><div><div class="ui-kaart__titel">Kortingsactie: {{ $settings?->discountPercentage }}% korting</div><div class="ui-kaart__sub">Filmpje maken, delen en De Gouden Bol taggen</div></div></div>
                        <div class="ui-kaart__inhoud !pt-2">
                            @if ($entry->discount_status === DiscountStatus::Rejected && $entry->discount_note)
                                <div class="ui-melding ui-melding--waarschuwing mb-4"><span class="ui-melding__icoon"><x-ui.icoon naam="waarschuwing" dikte="2" /></span><div class="ui-melding__inhoud"><span class="ui-melding__titel">Nog niet goedgekeurd</span>{{ $entry->discount_note }}</div></div>
                            @elseif ($entry->discount_status === DiscountStatus::Submitted)
                                <p class="mb-4 text-[0.88rem]">We bekijken je post. Iets vergeten? Je kunt een nieuwe link of schermafbeelding sturen.</p>
                            @else
                                <p class="mb-4 text-[0.88rem]">Plaats het filmpje op social media, tag De Gouden Bol en zet hieronder de link naar je post. Een schermafbeelding mag ook. Na goedkeuring betaal je de actieprijs.</p>
                            @endif
                            <form method="post" action="{{ route('portaal.aanmelding.korting', $company) }}" enctype="multipart/form-data" class="grid gap-4">
                                @csrf
                                <div class="ui-veld"><label class="ui-label" for="link">Link naar je post</label><input id="link" name="link" type="url" class="ui-invoer" value="{{ old('link', $entry->discount_proof_url) }}" placeholder="https://www.instagram.com/p/…"></div>
                                <div class="ui-veld"><label class="ui-label" for="bewijs">Schermafbeelding <span class="ui-label__optioneel">optioneel</span></label><input id="bewijs" name="bewijs" type="file" accept="image/*" class="ui-invoer"></div>
                                <div><button type="submit" class="ui-knop">Bewijs versturen</button></div>
                            </form>
                            <p class="mt-3 text-[0.8rem] text-gedempt">Je plek blijft gereserveerd tot {{ DutchTime::format($entry->reservation_expires_at, 'D MMMM YYYY') }}.</p>
                        </div>
                    </section>
                @endif

                <div class="ui-hulpkaart">
                    <div class="ui-hulpkaart__kop"><x-ui.icoon naam="info" class="h-5 w-5 text-goud-tekst" /> Goed om te weten</div>
                    <p class="ui-hulpkaart__tekst">Je tarief heeft nooit invloed op de beoordeling. Het panel proeft blind en ziet geen namen, prijzen of sponsors.</p>
                </div>
            </div>
        </div>
    @endif
</x-layouts.portal>
