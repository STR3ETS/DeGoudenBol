@if (Route::has('nieuwsbrief.aanmelden'))
<div class="footer-ask" id="nieuwsbrief">
    <b>Nieuwsbrief.</b>
    @if (session('nieuwsbrief'))
        <span role="status">{{ session('nieuwsbrief') }}</span>
    @else
        {{-- Geen CSRF-token: deze pagina's komen uit de paginacache. De honeypot hieronder vangt bots. --}}
        <form method="post" action="{{ route('nieuwsbrief.aanmelden') }}" style="display:inline-flex;flex-wrap:wrap;gap:8px;align-items:center;margin-left:6px">
            <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Bedrijfsnaam <input type="text" name="bedrijfsnaam" tabindex="-1" autocomplete="off"></label></div>
            <label for="nieuwsbrief-email" class="sr-only">E-mailadres</label>
            <input id="nieuwsbrief-email" class="f-input" type="email" name="email" value="{{ old('email') }}" placeholder="je@voorbeeld.nl" required autocomplete="email" style="width:220px;padding:9px 12px">
            <button type="submit" class="f-submit" style="width:auto;padding:10px 18px">Aanmelden</button>
        </form>
        @error('email')<span style="color:var(--gold)">{{ $message }}</span>@enderror
        <span style="display:block;font-size:.78rem;opacity:.75;margin-top:6px">Uitslagen, finale en de cadeaubonnenactie. Bevestiging per e-mail; afmelden kan altijd.</span>
    @endif
</div>
@endif
