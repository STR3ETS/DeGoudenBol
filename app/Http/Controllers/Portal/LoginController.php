<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Notifications\MagicLinkNotification;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Portaal-login: e-mail + wachtwoord, of een inloglink per mail.
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'e-mailadres', 'password' => 'wachtwoord']);

        $this->ensureNotRateLimited($request);

        if (! Auth::guard('participant')->attempt(['email' => Str::lower($credentials['email']), 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages(['email' => 'De combinatie van e-mailadres en wachtwoord klopt niet.']);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();
        $request->user('participant')->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('portaal.dashboard'));
    }

    public function sendMagicLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [], ['email' => 'e-mailadres']);

        $this->ensureNotRateLimited($request);
        RateLimiter::hit($this->throttleKey($request), 60);

        ParticipantUser::query()->where('email', Str::lower($data['email']))->first()?->notify(new MagicLinkNotification);

        return back()->with('status', 'Als dit e-mailadres bij ons bekend is, ontvangt u binnen enkele minuten een inloglink.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('participant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portaal.inloggen');
    }

    private function ensureNotRateLimited(Request $request): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            throw ValidationException::withMessages(['email' => 'Te veel pogingen. Probeer het over een minuut opnieuw.']);
        }
    }

    private function throttleKey(Request $request): string
    {
        return 'portal-login:'.Str::lower((string) $request->input('email')).'|'.$request->ip();
    }
}
