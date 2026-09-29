@php
    use App\Domain\Participants\Enums\EntryStatus;
    use App\Support\DutchTime;

    $timeline = ['Aangemeld', 'Ingepland', 'Getest', 'Uitslag'];
@endphp
<x-layouts.portal title="Overzicht">
    <x-ui.eyebrow>Welkom, {{ $user->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-8">Uw <em>deelname</em>{{ $edition ? ' aan De Gouden Bol '.$edition->year : '' }}</h1>

    @forelse ($companies as $company)
        @php $entry = $company->entries->first(); @endphp
        <x-ui.card class="mb-6">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="kop-3">{{ $company->name }}</h2>
                    <p class="text-[0.85rem] text-gedempt">{{ $company->type->getLabel() }}@if ($company->kvk_number) · KvK {{ $company->kvk_number }}@endif</p>
                </div>
                @if ($entry)
                    <x-ui.chip :status="$entry->status === EntryStatus::PendingPayment ? 'waarschuwing' : ($entry->status->isPubliclyVisible() ? 'succes' : 'info')" :dot="true">{{ $entry->status->getLabel() }}</x-ui.chip>
                @endif
            </div>

            @if ($entry)
                @php $currentStep = $entry->status->timelineStep(); @endphp
                <ol class="mb-6 grid gap-3 sm:grid-cols-4" aria-label="Voortgang van de inzending">
                    @foreach ($timeline as $index => $label)
                        @php $number = $index + 1; @endphp
                        <li class="rounded-blok border p-3 text-[0.8rem] {{ $currentStep !== null && $number <= $currentStep ? 'border-goud bg-goud-licht' : 'border-rand bg-room' }}">
                            <span class="font-kop text-[1.1rem] font-bold {{ $currentStep !== null && $number <= $currentStep ? 'text-goud-tekst' : 'text-gedempt' }}">{{ $number }}</span>
                            <span class="ml-1 font-semibold">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>

                <dl class="grid gap-x-8 gap-y-2 text-[0.9rem] sm:grid-cols-[160px_1fr]">
                    <dt class="text-gedempt">Provincie</dt><dd>{{ $entry->province->name }}</dd>
                    <dt class="text-gedempt">Op de site als</dt><dd>{{ $entry->public_name }}</dd>
                    @if ($entry->status === EntryStatus::PendingPayment)
                        <dt class="text-gedempt">Betaling</dt>
                        <dd>
                            Nog niet afgerond.
                            @if ($entry->order && ! $entry->isReservationExpired())
                                <a href="{{ route('aanmelden.status', $entry->order) }}" class="text-goud-tekst underline">Betaling afronden</a>
                                (plek gereserveerd tot {{ DutchTime::format($entry->reservation_expires_at, 'HH:mm') }})
                            @endif
                        </dd>
                    @elseif ($entry->order?->invoice)
                        <dt class="text-gedempt">Factuur</dt>
                        <dd>{{ $entry->order->invoice->number }} · {{ $entry->order->invoice->formattedTotal() }}@if (Route::has('portaal.facturen.toon')) · <a href="{{ route('portaal.facturen.toon', $entry->order->invoice) }}" class="text-goud-tekst underline">bekijken</a>@endif</dd>
                    @endif
                    @if ($entry->publishedItem())
                        <dt class="text-gedempt">Uitslag</dt>
                        <dd>
                            @if ($entry->publicTotal() !== null)
                                Cijfer <strong>{{ number_format($entry->publicTotal(), 1, ',', '.') }}</strong> · officieel getest
                            @else
                                Vertrouwelijk resultaat
                            @endif
                            · <a href="{{ route('portaal.uitslag', $company) }}" class="font-bold text-goud-tekst underline">bekijk uw uitslag</a>
                        </dd>
                    @endif
                    @if ($entry->deliverySlot)
                        <dt class="text-gedempt">Aanleveren</dt>
                        <dd>
                            {{ ucfirst(DutchTime::format($entry->deliverySlot->starts_at, 'dddd D MMMM, HH:mm')) }} – {{ DutchTime::format($entry->deliverySlot->ends_at, 'HH:mm') }}
                            · code <strong>{{ $entry->delivery_code }}</strong>
                            · <a href="{{ route('portaal.planning.bewijs', $company) }}" class="text-goud-tekst underline">aanleverbewijs</a>
                        </dd>
                    @elseif (in_array($entry->status, [EntryStatus::Registered, EntryStatus::FreshnessExpired], true))
                        <dt class="text-gedempt">Aanleveren</dt>
                        <dd><a href="{{ route('portaal.planning', $company) }}" class="font-bold text-goud-tekst underline">Kies een aanlevermoment</a>@if ($edition?->first_test_day) · testdagen {{ DutchTime::date($edition->first_test_day) }} t/m {{ DutchTime::date($edition->last_test_day) }}@endif</dd>
                    @elseif ($edition?->first_test_day)
                        <dt class="text-gedempt">Testperiode</dt><dd>{{ DutchTime::date($edition->first_test_day) }} t/m {{ DutchTime::date($edition->last_test_day) }}</dd>
                    @endif
                </dl>
            @else
                <x-ui.empty-state titel="Nog geen inschrijving voor deze editie">
                    @if (Route::has('aanmelden'))
                        <x-ui.button :href="route('aanmelden')">Aanmelden</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @endif
        </x-ui.card>
    @empty
        <x-ui.empty-state titel="Aan dit account is nog geen bedrijf gekoppeld" tekst="Meld een bedrijf aan of vraag de eigenaar om u als medewerker toe te voegen." />
    @endforelse
</x-layouts.portal>
