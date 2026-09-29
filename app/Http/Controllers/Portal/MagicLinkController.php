<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Participants\Services\MagicLinkService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MagicLinkController extends Controller
{
    public function __invoke(Request $request, MagicLinkService $magicLinks): RedirectResponse
    {
        $result = $magicLinks->consume((string) $request->query('token', ''));

        if ($result === null) {
            return redirect()
                ->route('portaal.inloggen')
                ->with('status', 'Deze inloglink is verlopen of al gebruikt. Vraag een nieuwe aan.');
        }

        Auth::guard('participant')->login($result['user'], remember: true);
        $request->session()->regenerate();
        $result['user']->forceFill(['last_login_at' => now()])->save();

        return redirect()->to($result['redirect'] ?? route('portaal.dashboard'));
    }
}
