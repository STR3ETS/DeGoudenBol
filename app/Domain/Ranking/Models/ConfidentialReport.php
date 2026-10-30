<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Enums\ReportStatus;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persoonlijk keuringsrapport voor iedere deelnemer (briefing 29 okt §7): scores per onderdeel,
 * eindscore, sterke punten, verbeterpunten en de algemene indruk. Het concept komt uit de
 * geanonimiseerde panelnotities; de redactie maakt het af en publiceert het binnen de termijn.
 * Staat los van de landelijke ranking, die pas op de publicatiedatum openbaar wordt.
 *
 * @property ReportStatus $status
 */
#[Fillable(['entry_id', 'strengths', 'opportunities', 'general_impression', 'course_suggestion', 'tested_on', 'due_at', 'status', 'edited_by', 'published_at'])]
class ConfidentialReport extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'tested_on' => 'immutable_date',
            'due_at' => 'immutable_datetime',
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

    public function isOverdue(): bool
    {
        return ! $this->isPublished() && $this->due_at !== null && $this->due_at->isPast();
    }

    /**
     * Nog niet gepubliceerde rapporten, oudste termijn eerst.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', ReportStatus::Published->value)->orderBy('due_at');
    }
}
