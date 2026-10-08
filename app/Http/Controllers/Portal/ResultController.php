<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\ObjectionStatus;
use App\Domain\Participants\Events\ObjectionSubmitted;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\Objection;
use App\Domain\Participants\Services\CompanyAccess;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Actions\RespondToFinalInvitation;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

/**
 * Uitslag in het portaal: cijfer, positie op de Voorlijst, vertrouwelijk rapport en bezwaar.
 */
class ResultController extends Controller
{
    public function show(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        [$company, $entry, $edition] = $this->resolve($request, $access, $bedrijf);

        $item = $entry?->publishedItem();
        $position = $entry && $item?->isPublic()
            ? RankingSnapshot::latestForProvince($edition->getKey(), $entry->province_id)?->positions()->where('entry_id', $entry->getKey())->first()
            : null;

        return view('portal.uitslag', [
            'company' => $company,
            'entry' => $entry,
            'edition' => $edition,
            'item' => $item,
            'position' => $position,
            'finalist' => $entry?->finalist,
            'report' => $entry?->confidentialReport?->isPublished() ? $entry->confidentialReport : null,
            'objections' => $entry?->objections()->orderByDesc('submitted_at')->get() ?? collect(),
            'objectionDeadline' => $entry?->published_at?->addWeekdays(Objection::WINDOW_WORKING_DAYS),
        ]);
    }

    public function objection(Request $request, CompanyAccess $access, AuditLogger $audit, string $bedrijf): RedirectResponse
    {
        [$company, $entry] = $this->resolve($request, $access, $bedrijf, [CompanyUserRole::Owner]);

        abort_if($entry === null || $entry->published_at === null, 404);

        if ($entry->published_at->addWeekdays(Objection::WINDOW_WORKING_DAYS)->isPast()) {
            return back()->withErrors(['reason' => 'De termijn van drie werkdagen na publicatie is verstreken.']);
        }

        if ($entry->objections()->whereIn('status', [ObjectionStatus::Submitted, ObjectionStatus::Reviewing])->exists()) {
            return back()->withErrors(['reason' => 'Er loopt al een bezwaar voor deze inschrijving.']);
        }

        $data = $request->validate(['reason' => ['required', 'string', 'min:20', 'max:3000']], [], ['reason' => 'toelichting']);

        $objection = $entry->objections()->create([
            'participant_user_id' => $request->user('participant')->getKey(),
            'reason' => $data['reason'],
            'status' => ObjectionStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $audit->record('objection.submitted', $objection, ['entry' => $entry->ulid], null);

        ObjectionSubmitted::dispatch($objection);

        return redirect()->route('portaal.uitslag', $company)->with('status', 'Uw bezwaar is ingediend. Twee beslissers buiten het panel en de registratie beoordelen het.');
    }

    public function respondToFinal(Request $request, CompanyAccess $access, RespondToFinalInvitation $respond, string $bedrijf): RedirectResponse
    {
        [$company, $entry] = $this->resolve($request, $access, $bedrijf, [CompanyUserRole::Owner]);
        $finalist = $entry?->finalist;

        abort_if($finalist === null || ! $finalist->status->participates(), 404);

        $data = $request->validate(['keuze' => ['required', 'in:bevestigen,afmelden']]);

        try {
            $respond($finalist, $data['keuze'] === 'bevestigen', $request->user('participant'));
        } catch (LogicException $exception) {
            return back()->withErrors(['keuze' => $exception->getMessage()]);
        }

        return redirect()->route('portaal.uitslag', $company)->with('status', $data['keuze'] === 'bevestigen'
            ? 'Uw deelname aan de landelijke finale is bevestigd.'
            : 'U bent afgemeld voor de finale. De provinciale titel blijft van u.');
    }

    /**
     * @param  list<CompanyUserRole>  $roles
     * @return array{0: Company, 1: Entry|null, 2: Edition|null}
     */
    private function resolve(Request $request, CompanyAccess $access, ?string $bedrijf, array $roles = []): array
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, $roles);
        $edition = Edition::current();
        $entry = $edition
            ? $company->entries()->where('edition_id', $edition->getKey())->with(['province', 'publicationItems.batch', 'confidentialReport', 'finalist'])->first()
            : null;

        return [$company, $entry, $edition];
    }
}
