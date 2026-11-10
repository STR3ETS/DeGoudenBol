<?php

namespace App\Http\Middleware;

use App\Domain\Participants\Services\PortalViewing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bewaakt het meekijken van een medewerker in het portaal: alleen lezen, rapport en uitslag dicht,
 * en de sessie eindigt zodra de medewerker uitlogt, zijn rol kwijt is of de tijd om is.
 * Voor een gewone deelnemer doet deze middleware niets.
 */
class RestrictPortalViewing
{
    public function __construct(private readonly PortalViewing $viewing) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->viewing->blocksChanges()) {
            return $next($request);
        }

        if ($this->viewing->current() === null) {
            $this->viewing->stop();

            return redirect()->route('portaal.inloggen')->with('status', 'Het meekijken is beëindigd. Start het opnieuw vanuit de backoffice.');
        }

        if ($request->routeIs('portaal.meekijken.stoppen')) {
            return $next($request);
        }

        if (! $request->isMethodSafe()) {
            if ($request->expectsJson()) {
                abort(403, 'Je kijkt mee: wijzigen kan niet.');
            }

            return redirect()->back(fallback: route('portaal.dashboard'))->with('status', 'Je kijkt mee: wijzigen kan niet.');
        }

        if ($request->routeIs(...PortalViewing::CLOSED_ROUTES)) {
            return redirect()->route('portaal.dashboard')->with('status', 'Rapport en uitslag blijven dicht tijdens het meekijken.');
        }

        return $next($request);
    }
}
