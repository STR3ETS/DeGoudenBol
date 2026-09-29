<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Enums\CorrectionCaseStatus;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Correctiedossier na publicatie: twee goedkeuringen, dan een nieuwe uitslag in de eerstvolgende
 * batch en dus een nieuwe snapshot. Het origineel blijft bewaard.
 *
 * @property array<string, mixed> $original_snapshot
 * @property array<string, mixed>|null $new_snapshot
 * @property CorrectionCaseStatus $status
 */
#[Fillable(['entry_id', 'reason', 'original_snapshot', 'new_snapshot', 'status', 'submitted_by', 'applied_at'])]
class CorrectionCase extends DomainModel
{
    public const int REQUIRED_APPROVALS = 2;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_snapshot' => 'array',
            'new_snapshot' => 'array',
            'status' => CorrectionCaseStatus::class,
            'applied_at' => 'immutable_datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }
}
