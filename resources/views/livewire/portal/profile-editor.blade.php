@php
    use App\Domain\Participants\Enums\ModerationStatus;
@endphp
<div>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <x-ui.eyebrow>{{ $company->name }}</x-ui.eyebrow>
            <h1 class="kop-pagina">Uw <em>profiel</em></h1>
        </div>
        <div class="flex flex-col items-end gap-2">
            <x-ui.chip :status="match ($profile->moderation_status) { ModerationStatus::Approved => 'succes', ModerationStatus::Pending => 'waarschuwing', ModerationStatus::Rejected => 'fout', default => 'neutraal' }" :dot="true">{{ $profile->moderation_status->getLabel() }}</x-ui.chip>
            @if ($profile->isPublished() && $profile->hasUnpublishedChanges())
                <span class="text-[0.75rem] text-gedempt">De site toont nog de laatst goedgekeurde versie.</span>
            @endif
        </div>
    </div>

    @if ($profile->moderation_status === ModerationStatus::Rejected && $profile->review_note)
        <div class="mb-6 rounded-blok border border-status-fout/30 bg-status-fout-bg p-4 text-[0.9rem] text-status-fout">
            <strong>Opmerking van de redactie:</strong> {{ $profile->review_note }}
        </div>
    @endif

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="flex flex-col gap-6">
            <x-ui.card>
                <h2 class="kop-3 mb-1 text-[1.25rem]">Op de site</h2>
                <p class="body-text mb-5 text-[0.85rem]">Deze tekst verschijnt op uw profielpagina na goedkeuring door de redactie.</p>
                <div class="flex flex-col gap-4">
                    <x-ui.field name="tagline" label="Korte omschrijving" wire:model.blur="tagline" placeholder="Bijvoorbeeld: familiebedrijf sinds 1948" help="Maximaal 140 tekens." />
                    <div>
                        <label for="veld-story" class="label">Uw verhaal</label>
                        <textarea id="veld-story" wire:model.blur="story" class="input min-h-[220px]" maxlength="3000" placeholder="Wie bent u, hoe bakt u, wat maakt uw oliebol bijzonder?"></textarea>
                        @error('story') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <x-ui.field name="specialtiesText" label="Specialiteiten" wire:model.blur="specialtiesText" placeholder="Oliebollen met krenten en rozijnen, appelbeignets" help="Scheid met komma's." />
                </div>
            </x-ui.card>

            <x-ui.card>
                <h2 class="kop-3 mb-5 text-[1.25rem]">Standplaats en seizoen</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field name="street" label="Straat" wire:model.blur="street" :required="true" />
                    <x-ui.field name="houseNumber" label="Huisnummer" wire:model.blur="houseNumber" :required="true" />
                    <x-ui.field name="postcode" label="Postcode" wire:model.blur="postcode" :required="true" />
                    <x-ui.field name="city" label="Plaats" wire:model.blur="city" :required="true" />
                    <x-ui.field name="seasonFrom" label="Seizoen van" type="date" wire:model.blur="seasonFrom" help="Leeg = het hele jaar open." />
                    <x-ui.field name="seasonTo" label="Seizoen tot en met" type="date" wire:model.blur="seasonTo" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <h2 class="kop-3 mb-1 text-[1.25rem]">Openingstijden</h2>
                <p class="body-text mb-5 text-[0.85rem]">De site toont hiermee "Nu open". Uitzonderingen zoals oudjaarsdag komen binnenkort.</p>
                <div class="flex flex-col gap-2">
                    @foreach ($weekdays as $weekday => $label)
                        <div class="grid grid-cols-[110px_1fr_1fr_auto] items-center gap-3 border-b border-rand py-2 text-[0.85rem] last:border-0">
                            <span class="font-semibold">{{ $label }}</span>
                            <input type="time" wire:model="hours.{{ $weekday }}.opens" class="input !min-h-0 !py-2" aria-label="{{ $label }} open vanaf" @disabled($hours[$weekday]['closed'] ?? false)>
                            <input type="time" wire:model="hours.{{ $weekday }}.closes" class="input !min-h-0 !py-2" aria-label="{{ $label }} open tot" @disabled($hours[$weekday]['closed'] ?? false)>
                            <label class="flex items-center gap-2 whitespace-nowrap"><input type="checkbox" wire:model.live="hours.{{ $weekday }}.closed" class="h-4 w-4 accent-goud"> Gesloten</label>
                        </div>
                    @endforeach
                </div>
                @error('hours.*.opens') <p class="field-error">{{ $message }}</p> @enderror
                @error('hours.*.closes') <p class="field-error">{{ $message }}</p> @enderror
            </x-ui.card>
        </div>

        <div class="flex flex-col gap-6">
            <x-ui.card>
                <h2 class="kop-3 mb-5 text-[1.25rem]">Online</h2>
                <div class="flex flex-col gap-4">
                    <x-ui.field name="website" label="Website" type="url" wire:model.blur="website" placeholder="https://" />
                    <x-ui.field name="instagram" label="Instagram" wire:model.blur="instagram" placeholder="gebruikersnaam" />
                    <x-ui.field name="facebook" label="Facebook" wire:model.blur="facebook" placeholder="pagina of link" />
                </div>
            </x-ui.card>

            <x-ui.card variant="zand">
                <h2 class="kop-3 mb-3 text-[1.1rem]">Opslaan</h2>
                <p class="body-text mb-4 text-[0.85rem]">Sla op als concept, of dien in zodat de redactie de tekst kan goedkeuren.</p>
                <div class="flex flex-col gap-3">
                    <x-ui.button type="submit" variant="outline" :breed="true" wire:loading.attr="disabled">Opslaan als concept</x-ui.button>
                    <x-ui.button type="button" wire:click="submitForReview" :breed="true" wire:loading.attr="disabled">Indienen ter beoordeling</x-ui.button>
                </div>
                <p class="mt-3 text-[0.75rem] text-gedempt">Foto's en logo uploaden volgt in een volgende versie van het portaal.</p>
            </x-ui.card>
        </div>
    </form>
</div>
