<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use App\Support\Portal\PersonalReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Het officiële beoordelingsrapport en de oorkonde, als A4-document dat de bakker afdrukt of als
 * pdf bewaart (briefing 29 okt §7: zichtbaar en downloadbaar in het portaal).
 */
class ReportController extends Controller
{
    public function show(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->first() : null;
        $report = $entry ? PersonalReport::for($entry) : null;

        abort_if($report === null, 404, 'Uw persoonlijke rapport is nog niet beschikbaar.');

        return view('portal.rapport', ['report' => $report, 'company' => $company, 'edition' => $edition]);
    }

    /**
     * Oorkonde "Officiële deelnemer" voor iedere bevestigde deelnemer.
     */
    public function certificate(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->first() : null;

        abort_if($entry === null || ! $entry->status->occupiesPlace() || $entry->status === EntryStatus::PendingPayment, 404);

        return view('portal.oorkonde', ['entry' => $entry, 'company' => $company, 'edition' => $edition]);
    }
}
