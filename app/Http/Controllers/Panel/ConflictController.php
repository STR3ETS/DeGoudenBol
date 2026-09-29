<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Models\PanelistConflict;
use App\Domain\Platform\Services\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Panelleden melden zelf hun belangenconflicten met deelnemende bedrijven. Het uitserveerschema
 * vertaalt die via de kluis naar uitsluitingen op testnummer.
 */
class ConflictController extends Controller
{
    public function index(Request $request): View
    {
        $edition = Edition::current();
        $term = trim((string) $request->query('q', ''));

        $candidates = $edition && $term !== ''
            ? Entry::query()
                ->where('edition_id', $edition->getKey())
                ->confirmed()
                ->where('public_name', 'like', "%{$term}%")
                ->with(['company.primaryLocation', 'province'])
                ->orderBy('public_name')
                ->limit(20)
                ->get()
            : collect();

        $conflicts = PanelistConflict::query()
            ->where('user_id', $request->user()->getKey())
            ->with('company')
            ->get();

        return view('panel.conflicten', [
            'panelist' => $request->attributes->get('panelist'),
            'term' => $term,
            'candidates' => $candidates,
            'conflicts' => $conflicts,
            'conflictCompanyIds' => $conflicts->pluck('company_id')->all(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $conflict = PanelistConflict::query()->firstOrCreate(
            ['user_id' => $request->user()->getKey(), 'company_id' => $data['company_id']],
            ['note' => $data['note'] ?? null],
        );

        $audit->record('panelist_conflict.reported', $conflict, ['company_id' => $conflict->company_id]);

        return redirect()->route('panel.conflicten')->with('status', 'Belangenconflict gemeld. U krijgt dit bedrijf niet uitgeserveerd.');
    }

    public function destroy(Request $request, PanelistConflict $conflict, AuditLogger $audit): RedirectResponse
    {
        abort_unless($conflict->user_id === (int) $request->user()->getKey(), 403);

        $audit->record('panelist_conflict.withdrawn', $conflict, ['company_id' => $conflict->company_id]);
        $conflict->delete();

        return redirect()->route('panel.conflicten')->with('status', 'Melding ingetrokken.');
    }
}
