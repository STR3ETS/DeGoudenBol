<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Participants\Services\PortalViewing;
use App\Filament\Resources\Companies\CompanyResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Een medewerker stopt met meekijken en gaat terug naar het bakkerdossier in de backoffice.
 */
class PortalViewingController extends Controller
{
    public function stop(PortalViewing $viewing): RedirectResponse
    {
        $company = $viewing->stop();

        if ($company === null) {
            return redirect()->route('portaal.dashboard');
        }

        return redirect()->to(CompanyResource::getUrl('view', ['record' => $company]));
    }
}
