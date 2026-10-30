<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Enums\ReportStatus;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Domain\Ranking\Notifications\ReportAvailableNotification;
use App\Models\User;
use App\Support\Mail\SendOnce;
use InvalidArgumentException;

/**
 * Persoonlijk rapport vrijgeven (briefing 29 okt §7): zichtbaar en downloadbaar in het portaal en
 * één mail naar uitsluitend de eigenaren van de juiste deelnemer. Los van de landelijke ranking.
 */
final class PublishPersonalReport
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SendOnce $mail,
    ) {}

    public function __invoke(ConfidentialReport $report, User $editor): ConfidentialReport
    {
        $entry = $report->entry()->with('company')->firstOrFail();

        if ($entry->linked_at === null) {
            throw new InvalidArgumentException('Het rapport kan pas vrij na een definitieve, gekoppelde uitslag.');
        }

        if (! $report->isPublished()) {
            $report->forceFill([
                'status' => ReportStatus::Published,
                'published_at' => now(),
                'edited_by' => $editor->getKey(),
            ])->save();

            $this->audit->record('report.published', $report, ['entry' => $entry->ulid, 'late' => $report->due_at?->isPast() ?? false], $editor);
        }

        foreach ($entry->company->owners() as $owner) {
            $this->mail->send($owner, new ReportAvailableNotification($entry), "report:{$report->getKey()}:available");
        }

        return $report;
    }
}
