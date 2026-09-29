<?php

namespace App\Http\Middleware;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\PoolStatus;
use App\Domain\Testing\Models\Panelist;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alleen medewerkers met de rol panellid die in de panelpool staan mogen de panel-app in.
 * Het panellid-record wordt aan het request gehangen; de app werkt verder alleen met die code.
 */
class EnsurePanelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->is_active || ! $user->hasRole(StaffRole::Panelist->value)) {
            abort(403, 'Alleen panelleden hebben toegang tot de panel-app.');
        }

        $panelist = Panelist::query()->where('user_id', $user->getKey())->where('pool_status', PoolStatus::Active)->first();

        if ($panelist === null) {
            abort(403, 'U staat (nog) niet in de actieve panelpool. Neem contact op met de testcoördinatie.');
        }

        $request->attributes->set('panelist', $panelist);

        return $next($request);
    }
}
