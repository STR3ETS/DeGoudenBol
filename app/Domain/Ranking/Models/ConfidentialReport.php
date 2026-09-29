<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Enums\ReportStatus;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vertrouwelijk rapport voor de deelnemer: sterke punten eerst, dan ontwikkelkansen, positieve toon.
 * Het concept komt uit de geanonimiseerde panelnotities; de redactie maakt het af.
 *
 * @property ReportStatus $status
 */
#[Fillable(['entry_id', 'strengths', 'opportunities', 'course_suggestion', 'status', 'edited_by', 'published_at'])]
class ConfidentialReport extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'published_at' => 'immutable_datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function isPublished(): bool
    {
        return $this->status === ReportStatus::Published;
    }
}
