<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Marketing\Services\PressReleaseDocument;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Filament\Resources\ConfidentialReports\ConfidentialReportResource;
use App\Filament\Resources\Entries\EntryResource;
use App\Filament\Resources\PressReleases\PressReleaseResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Portal\PersonalReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * De organisatie maakt certificaten, rapporten en persberichten als A4-document vanuit de backoffice
 * (verzoek Raphael 24 nov 2026, naar de aangeleverde sjablonen). Eén of meer tegelijk; de gegevens komen
 * uit het systeem. Wie een onderdeel in de backoffice mag zien, mag er ook het document van maken.
 */
class DocumentController extends Controller
{
    public const int MAX_PER_REQUEST = 200;

    /**
     * Certificaat van deelname voor iedere bevestigde deelnemer in de selectie.
     */
    public function certificates(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guard(EntryResource::canViewAny())) {
            return $redirect;
        }

        $entries = Entry::query()
            ->whereKey($this->ids($request))
            ->with(['edition', 'company', 'confidentialReport'])
            ->orderBy('public_name')
            ->get()
            ->filter(fn (Entry $entry) => self::hasCertificate($entry))
            ->values();

        abort_if($entries->isEmpty(), 404, 'Geen bevestigde deelnemers in deze selectie.');

        return view('documents.certificate', [
            'entries' => $entries,
            'title' => $entries->count() === 1 ? 'Certificaat '.$entries->first()->public_name : 'Certificaten van deelname',
            'backUrl' => EntryResource::getUrl('index'),
            'backLabel' => 'Terug naar de inschrijvingen',
            'hint' => $entries->count() === 1 ? $entries->first()->public_name : $entries->count().' certificaten, één per pagina',
        ]);
    }

    /**
     * Rapport per deelnemer. De redactie ziet ook een rapport dat nog niet is vrijgegeven, als concept.
     */
    public function reports(Request $request, AuditLogger $audit): View|RedirectResponse
    {
        if ($redirect = $this->guard(ConfidentialReportResource::canViewAny())) {
            return $redirect;
        }

        $reports = ConfidentialReport::query()
            ->whereKey($this->ids($request))
            ->with(['entry.company.primaryLocation', 'entry.edition', 'entry.province', 'entry.publicationItems'])
            ->get()
            ->map(fn (ConfidentialReport $report) => $report->entry ? PersonalReport::for($report->entry->setRelation('confidentialReport', $report), preview: true) : null)
            ->filter()
            ->sortBy(fn (PersonalReport $report) => $report->entry->public_name)
            ->values();

        abort_if($reports->isEmpty(), 404, 'Geen rapporten in deze selectie.');

        // Rapporten koppelen een deelnemer aan zijn scores: vastleggen wie ze heeft uitgedraaid.
        $audit->record('report.documents_generated', null, ['entry_ids' => $reports->map(fn (PersonalReport $report) => $report->entry->getKey())->all()]);

        return view('documents.report', [
            'reports' => $reports,
            'title' => $reports->count() === 1 ? 'Rapport '.$reports->first()->entry->public_name : 'Rapporten',
            'backUrl' => ConfidentialReportResource::getUrl('index'),
            'backLabel' => 'Terug naar de rapporten',
            'hint' => $reports->count() === 1 ? $reports->first()->entry->public_name : $reports->count().' rapporten, één per pagina, op naam gesorteerd',
        ]);
    }

    /**
     * Persbericht als document, ook onder embargo (dat staat er dan groot op).
     */
    public function pressRelease(int $pressRelease): View|RedirectResponse
    {
        if ($redirect = $this->guard(PressReleaseResource::canViewAny())) {
            return $redirect;
        }

        $release = PressRelease::query()->findOrFail($pressRelease);

        return view('documents.press-release', [
            'document' => PressReleaseDocument::for($release),
            'title' => 'Persbericht '.$release->title,
            'backUrl' => PressReleaseResource::getUrl('edit', ['record' => $release]),
            'backLabel' => 'Terug naar het persbericht',
            'hint' => $release->scopeLabel(),
        ]);
    }

    public static function hasCertificate(Entry $entry): bool
    {
        return $entry->status->occupiesPlace() && $entry->status !== EntryStatus::PendingPayment;
    }

    /**
     * Gasten gaan naar de backoffice-login; een medewerker zonder de juiste rol krijgt geen toegang.
     */
    private function guard(bool $allowed): ?RedirectResponse
    {
        $user = auth('web')->user();

        if (! $user instanceof User) {
            return redirect('/admin/login');
        }

        abort_unless($user->is_active && $allowed, 403);

        return null;
    }

    /**
     * @return list<int>
     */
    private function ids(Request $request): array
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(self::MAX_PER_REQUEST)
            ->values()
            ->all();

        abort_if($ids === [], 404);

        return $ids;
    }
}
