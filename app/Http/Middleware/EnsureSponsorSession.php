<?php

namespace App\Http\Middleware;

use App\Domain\Commerce\Models\Sponsor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sponsoren hebben geen wachtwoord: de persoonlijke beheerlink opent een sessie voor één sponsor.
 * Zonder sessie stuurt deze middleware naar de pagina waar je een nieuwe link aanvraagt.
 */
class EnsureSponsorSession
{
    public const string SESSION_KEY = 'sponsor_ulid';

    public function handle(Request $request, Closure $next): Response
    {
        $ulid = $request->session()->get(self::SESSION_KEY);
        $sponsor = $ulid ? Sponsor::query()->where('ulid', $ulid)->first() : null;

        if ($sponsor === null) {
            return redirect()->route('sponsoring.beheer')->with('status', 'Uw sessie is verlopen. Vraag hieronder een nieuwe beheerlink aan.');
        }

        $request->attributes->set('sponsor', $sponsor);

        return $next($request);
    }
}
