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
 * Het rapport en het certificaat van deelname, als A4-document dat de bakker afdrukt of als pdf bewaart
 * (briefing 29 okt §7: zichtbaar en downloadbaar in het portaal). Opmaak naar de sjablonen van 24 nov 2026;
 * dezelfde weergaven gebruikt de organisatie in de backoffice (Admin\DocumentController).
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

        return view('documents.report', [
            'reports' => collect([$report]),
            'title' => 'Rapport '.$company->name,
            'backUrl' => route('portaal.uitslag', $company),
            'backLabel' => 'Terug naar het portaal',
            'hint' => $company->name,
        ]);
    }

    /**
     * Certificaat van deelname voor iedere bevestigde deelnemer (de route heet nog "oorkonde").
     */
    public function certificate(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->first() : null;

        abort_if($entry === null || ! $entry->status->occupiesPlace() || $entry->status === EntryStatus::PendingPayment, 404);

        return view('documents.certificate', [
            'entries' => collect([$entry->loadMissing(['edition', 'confidentialReport'])]),
            'title' => 'Certificaat '.$company->name,
            'backUrl' => route('portaal.dashboard'),
            'backLabel' => 'Terug naar het portaal',
            'hint' => $entry->public_name,
        ]);
    }
}
