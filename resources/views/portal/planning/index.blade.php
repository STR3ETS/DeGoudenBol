@php
    use App\Domain\Participants\Enums\EntryStatus;
    use App\Support\DutchTime;
@endphp
<x-layouts.portal title="Planning">
    <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
    <h1 class="kop-pagina mb-3">Uw <em>aanlevermoment</em></h1>
    <p class="body-text mb-8 max-w-[640px]">
        @if ($edition)
            U levert {{ $edition->settings->piecesPerEntry }} oliebollen aan in neutrale verpakking, zonder logo of naam. Het panel proeft binnen {{ intdiv($edition->settings->freshnessWindowMinutes, 60) }} uur na ontvangst. Kies hieronder een tijdslot; u ontvangt daarna een aanleverbewijs met QR-code.
        @else
            Er is op dit moment geen actieve editie.
        @endif
    </p>

    @if ($entry === null)
        <x-ui.empty-state titel="Geen inschrijving voor deze editie" tekst="Meld u eerst aan; daarna kunt u hier een aanlevermoment kiezen." />
    @else
        @if ($entry->deliverySlot)
            <x-ui.card variant="zand" class="mb-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <x-ui.eyebrow>Gekozen slot</x-ui.eyebrow>
                        <h2 class="kop-3">{{ ucfirst(DutchTime::format($entry->deliverySlot->starts_at, 'dddd D MMMM, HH:mm')) }} – {{ DutchTime::format($entry->deliverySlot->ends_at, 'HH:mm') }}</h2>
                        @if ($entry->deliverySlot->testLocation)
                            <p class="mt-1 text-[0.9rem] text-gedempt">{{ $entry->deliverySlot->testLocation->name }} · {{ $entry->deliverySlot->testLocation->street }}, {{ $entry->deliverySlot->testLocation->city }}</p>
                        @endif
                        <p class="mt-3 text-[0.9rem]">Aanlevercode: <strong class="font-kop text-[1.3rem] tracking-[0.12em]">{{ $entry->delivery_code }}</strong></p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <x-ui.chip :status="$entry->status === EntryStatus::Scheduled ? 'succes' : 'info'" :dot="true">{{ $entry->status->getLabel() }}</x-ui.chip>
                        <x-ui.button :href="route('portaal.planning.bewijs', $company)" size="sm">Aanleverbewijs (QR)</x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        @endif

        @if ($entry->status === EntryStatus::FreshnessExpired)
            <x-ui.chip status="waarschuwing" :dot="true" class="mb-6">Het vorige monster was te laat voor de proeverij. Kies een nieuw aanlevermoment.</x-ui.chip>
        @endif

        @if (! $canChoose)
            <x-ui.card>
                <p class="text-[0.9rem] text-gedempt">Uw monster is ontvangen; het aanlevermoment kan niet meer worden gewijzigd.</p>
            </x-ui.card>
        @elseif ($slots->isEmpty())
            <x-ui.empty-state titel="Nog geen aanleverslots" tekst="De testcoördinatie zet de tijdslots klaar vóór de eerste testdag. U ontvangt bericht zodra u kunt kiezen." />
        @else
            <form method="post" action="{{ route('portaal.planning.kiezen', $company) }}">
                @csrf
                <x-ui.card class="!p-0 overflow-hidden">
                    @error('slot')
                        <p class="field-error px-6 pt-5">{{ $message }}</p>
                    @enderror
                    <ul>
                        @foreach ($slots as $slot)
                            @php
                                $available = max(0, $slot->capacity - $slot->taken_count);
                                $isCurrent = $entry->delivery_slot_id === $slot->getKey();
                                $disabled = ! $isCurrent && ($available === 0 || ! $canChange);
                            @endphp
                            <li class="border-b border-rand last:border-0">
                                <label class="flex cursor-pointer flex-wrap items-center gap-4 px-6 py-4 {{ $isCurrent ? 'bg-goud-licht' : 'hover:bg-room' }} {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}">
                                    <input type="radio" name="slot" value="{{ $slot->getKey() }}" class="h-5 w-5 accent-goud" @checked($isCurrent) @disabled($disabled)>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold">{{ ucfirst(DutchTime::format($slot->starts_at, 'dddd D MMMM')) }}, {{ DutchTime::format($slot->starts_at, 'HH:mm') }} – {{ DutchTime::format($slot->ends_at, 'HH:mm') }}</span>
                                        @if ($slot->testLocation)
                                            <span class="block text-[0.8rem] text-gedempt">{{ $slot->testLocation->name }}, {{ $slot->testLocation->city }}</span>
                                        @endif
                                    </span>
                                    @if ($isCurrent)
                                        <x-ui.chip status="goud">Uw keuze</x-ui.chip>
                                    @elseif ($available === 0)
                                        <x-ui.chip status="neutraal">Vol</x-ui.chip>
                                    @else
                                        <span class="text-[0.8rem] text-gedempt">{{ $available }} {{ $available === 1 ? 'plek' : 'plekken' }} vrij</span>
                                    @endif
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
                @if ($canChange)
                    <div class="mt-6 flex flex-wrap items-center gap-4">
                        <x-ui.button type="submit">{{ $entry->deliverySlot ? 'Slot wijzigen' : 'Slot vastleggen' }}</x-ui.button>
                        <span class="text-[0.8rem] text-gedempt">Wijzigen kan tot het gekozen slot begint.</span>
                    </div>
                @endif
            </form>
        @endif
    @endif
</x-layouts.portal>
