@php
    use App\Support\SiteLinks;

    $columns = collect(config('site.footer'))
        ->map(fn (array $links) => SiteLinks::resolve($links))
        ->filter(fn (array $links) => $links !== []);
@endphp
<footer class="footer">
    <div class="site-container">
        <div class="grid gap-12 border-b border-room-decor/40 pb-12 md:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr]">
            <div>
                <x-ui.logo :dark="true" class="mb-4" />
                <p class="max-w-[260px] text-[0.85rem] leading-relaxed text-room-meta">
                    De onafhankelijke oliebollenkeuring van Nederland. Blind beoordeeld, openbaar gepubliceerd.
                </p>
                @if (Route::has('nieuwsbrief.aanmelden'))
                    <div id="nieuwsbrief" class="mt-6 max-w-[320px]">
                        <div class="footer__titel">Nieuwsbrief</div>
                        @if (session('nieuwsbrief'))
                            <p class="text-[0.85rem] leading-relaxed text-goud" role="status">{{ session('nieuwsbrief') }}</p>
                        @else
                            <form method="post" action="{{ route('nieuwsbrief.aanmelden') }}" class="flex flex-col gap-2 sm:flex-row">
                                {{-- Geen CSRF-token: deze pagina's komen uit de paginacache. Honeypot hieronder. --}}
                                <div class="absolute -left-[9999px]" aria-hidden="true"><label>Bedrijfsnaam <input type="text" name="bedrijfsnaam" tabindex="-1" autocomplete="off"></label></div>
                                <label for="nieuwsbrief-email" class="sr-only">E-mailadres</label>
                                <input id="nieuwsbrief-email" type="email" name="email" value="{{ old('email') }}" placeholder="je@voorbeeld.nl" required autocomplete="email" class="input !border-room-decor !bg-transparent !text-room placeholder:!text-room-decor">
                                <button type="submit" class="btn-pill btn--sm shrink-0">Aanmelden</button>
                            </form>
                            @error('email')<p class="mt-1 text-[0.78rem] text-goud">{{ $message }}</p>@enderror
                            <p class="mt-2 text-[0.72rem] text-room-decor">Uitslagen, finale en de cadeaubonnenactie. Bevestiging per e-mail; afmelden kan altijd.</p>
                        @endif
                    </div>
                @endif
            </div>

            @foreach ($columns as $title => $links)
                <div>
                    <div class="footer__titel">{{ $title }}</div>
                    <ul class="flex flex-col gap-2.5">
                        @foreach ($links as $link)
                            <li><a href="{{ $link['url'] }}" class="footer__link">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-8 text-[0.75rem] text-room-decor">
            <div>&copy; {{ now()->year }} De Gouden Bol &middot; Oliebollenkeuring Nederland</div>
            <div>Made with care by <a href="https://eazyonline.nl" class="font-bold text-goud no-underline" rel="noopener">Eazyonline</a></div>
        </div>
    </div>
</footer>
