<x-filament-panels::page>
    <div class="dgb-dash grid gap-6 xl:grid-cols-5">
        <div class="flex flex-col gap-6 xl:col-span-3">
            <div class="dgb-card overflow-hidden">
                <div class="dgb-card-head">
                    <span class="dgb-icoon bg-goud text-espresso"><x-filament::icon icon="heroicon-o-qr-code" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="dgb-card-title">Aanleverbewijs scannen</p>
                        <p class="dgb-card-sub">Camera (Chrome/Edge/Android) of typ de code van het bewijs.</p>
                    </div>
                    <button type="button" class="dgb-chip dgb-chip--goud" data-intake-scan>Camera starten</button>
                </div>
                <div class="px-5 pb-5">
                    <div class="hidden overflow-hidden rounded-[1rem] bg-espresso" data-intake-video-wrap>
                        <video class="aspect-video w-full object-cover" data-intake-video muted playsinline></video>
                    </div>
                    <p class="hidden pt-3 text-[0.75rem] text-gedempt" data-intake-scan-hint>Richt de camera op de QR-code van het aanleverbewijs.</p>
                    <div class="pt-4">
                        {{ $this->form }}
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6 xl:col-span-2">
            <div class="dgb-card overflow-hidden">
                <div class="dgb-card-head">
                    <span class="dgb-icoon bg-status-info-bg text-status-info"><x-filament::icon icon="heroicon-o-building-storefront" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="dgb-card-title">Inschrijving</p>
                        <p class="dgb-card-sub">Alleen zichtbaar voor Ontvangst & registratie</p>
                    </div>
                </div>
                <div class="px-5 pb-5">
                    @if ($this->entry)
                        <p class="font-kop text-[1.6rem] leading-tight font-bold text-espresso">{{ $this->entry['name'] }}</p>
                        <p class="text-[0.8rem] text-gedempt">{{ $this->entry['company'] }} · {{ $this->entry['province'] }}</p>
                        <dl class="mt-4 grid gap-y-2 text-[0.82rem] sm:grid-cols-[110px_1fr]">
                            <dt class="text-gedempt">Status</dt><dd class="font-bold {{ $this->entry['receivable'] ? 'text-status-succes' : 'text-status-fout' }}">{{ $this->entry['status'] }}</dd>
                            <dt class="text-gedempt">Slot</dt><dd>{{ $this->entry['slot'] ?? 'geen slot gekozen' }}</dd>
                            <dt class="text-gedempt">Allergenen</dt>
                            <dd>
                                @forelse ($this->entry['allergens'] as $allergen)
                                    <span class="dgb-pill bg-status-waarschuwing-bg text-status-waarschuwing">{{ $allergen }}</span>
                                @empty
                                    <span class="text-gedempt">geen opgegeven</span>
                                @endforelse
                            </dd>
                            @if ($this->entry['notes'])
                                <dt class="text-gedempt">Toelichting</dt><dd>{{ $this->entry['notes'] }}</dd>
                            @endif
                        </dl>
                        <button type="button" class="fi-btn fi-color-primary fi-size-lg mt-5 w-full justify-center rounded-full px-5 py-3 font-bold" wire:click="receive" wire:loading.attr="disabled" @disabled(! $this->entry['receivable'])>
                            <span wire:loading.remove wire:target="receive">Ontvangen en registreren</span>
                            <span wire:loading wire:target="receive">Bezig…</span>
                        </button>
                    @else
                        <div class="dgb-empty">
                            <p class="text-[0.85rem] font-bold text-espresso">Nog geen aanleverbewijs gescand</p>
                            <p class="text-[0.75rem] text-gedempt">Na het scannen verschijnen hier naam, provincie en allergenen ter controle.</p>
                        </div>
                    @endif
                </div>
            </div>

            @if ($this->lastSample)
                <div class="dgb-card p-5 text-center">
                    <p class="text-[0.65rem] font-bold tracking-[0.18em] text-gedempt uppercase">Laatste testnummer</p>
                    <p class="font-kop text-[3.4rem] leading-none font-bold tracking-[0.06em] text-espresso tabular-nums">{{ $this->lastSample['number'] ?? '–' }}</p>
                    <p class="mt-2 text-[0.78rem] text-gedempt">Vers tot {{ $this->lastSample['fresh_until'] }}</p>
                    @if ($this->lastSample['label_url'])
                        <a href="{{ $this->lastSample['label_url'] }}" target="_blank" rel="noopener" class="dgb-chip dgb-chip--goud mt-4">Etiket afdrukken</a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <script>
        (() => {
            const button = document.querySelector('[data-intake-scan]');
            const wrap = document.querySelector('[data-intake-video-wrap]');
            const video = document.querySelector('[data-intake-video]');
            const hint = document.querySelector('[data-intake-scan-hint]');
            if (!button || !video) return;

            let stream = null;
            let timer = null;

            const stop = () => {
                if (timer) clearInterval(timer);
                if (stream) stream.getTracks().forEach((track) => track.stop());
                stream = null;
                wrap.classList.add('hidden');
                hint.classList.add('hidden');
                button.textContent = 'Camera starten';
            };

            button.addEventListener('click', async () => {
                if (stream) return stop();

                if (!('BarcodeDetector' in window)) {
                    alert('Deze browser kan geen QR-codes lezen. Typ de aanlevercode in het veld.');
                    return;
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                } catch {
                    alert('Geen toegang tot de camera.');
                    return;
                }

                video.srcObject = stream;
                await video.play();
                wrap.classList.remove('hidden');
                hint.classList.remove('hidden');
                button.textContent = 'Camera stoppen';

                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                timer = setInterval(async () => {
                    try {
                        const codes = await detector.detect(video);
                        if (codes.length === 0) return;
                        const value = String(codes[0].rawValue || '').trim();
                        if (!value) return;
                        stop();
                        const wire = window.Livewire?.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
                        if (wire) {
                            await wire.set('data.code', value);
                            await wire.call('lookup');
                        }
                    } catch {}
                }, 400);
            });

            document.addEventListener('livewire:navigating', stop);
        })();
    </script>
</x-filament-panels::page>
