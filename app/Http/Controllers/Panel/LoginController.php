<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Platform\Enums\StaffRole;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login van de panel-app: e-mail en wachtwoord van het medewerkersaccount met de rol panellid.
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('panel.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'e-mailadres', 'password' => 'wachtwoord']);

        $key = 'panel-login:'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Te veel pogingen. Probeer het over een minuut opnieuw.']);
        }

        if (! Auth::guard('web')->attempt(['email' => Str::lower($credentials['email']), 'password' => $credentials['password']], true)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => 'De combinatie van e-mailadres en wachtwoord klopt niet.']);
        }

        $user = $request->user();

        if (! $user->is_active || ! $user->hasRole(StaffRole::Panelist->value)) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages(['email' => 'Dit account is geen panellid. Medewerkers van de organisatie loggen in op de backoffice (/admin).']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('panel.overzicht'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.inloggen');
    }
}
