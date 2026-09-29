<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Participants\Models\ParticipantUser;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Wachtwoord instellen (ingelogd) en herstellen (via e-mail), naast de inloglink.
 */
class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.auth.wachtwoord', ['user' => $request->user('participant')]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var ParticipantUser $user */
        $user = $request->user('participant');

        $rules = ['password' => ['required', 'confirmed', PasswordRule::min(12)]];

        if ($user->hasPassword()) {
            $rules['current_password'] = ['required', 'current_password:participant'];
        }

        $data = $request->validate($rules, [], ['password' => 'nieuw wachtwoord', 'current_password' => 'huidig wachtwoord']);

        $user->forceFill(['password' => $data['password']])->save();

        return back()->with('status', 'Uw wachtwoord is opgeslagen.');
    }

    public function forgot(): View
    {
        return view('portal.auth.vergeten');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [], ['email' => 'e-mailadres']);

        Password::broker('participants')->sendResetLink(['email' => Str::lower($data['email'])]);

        return back()->with('status', 'Als dit e-mailadres bij ons bekend is, ontvangt u een link om een nieuw wachtwoord in te stellen.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('portal.auth.herstellen', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)],
        ], [], ['email' => 'e-mailadres', 'password' => 'wachtwoord']);

        $status = Password::broker('participants')->reset(
            ['email' => Str::lower($data['email']), 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token']],
            function (ParticipantUser $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
                Auth::guard('participant')->login($user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route('portaal.dashboard')->with('status', 'Uw wachtwoord is opnieuw ingesteld en u bent ingelogd.');
    }
}
