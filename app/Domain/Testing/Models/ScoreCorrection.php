<?php

namespace App\Domain\Testing\Models;

use App\Domain\Testing\Enums\CorrectionStatus;
use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Correctie op een scorekaart (invoerfout), aangevraagd door de ene en goedgekeurd door
 * een andere scorecontroleur. De oorspronkelijke kaart blijft ongewijzigd bewaard.
 *
 * @property array<string, int> $before
 * @property array<string, int> $after
 * @property CorrectionStatus $status
 */
#[Fillable(['scorecard_id', 'reason', 'before', 'after', 'status', 'requested_by', 'approved_by', 'approved_at', 'rejection_reason'])]
class ScoreCorrection extends TestingModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'status' => CorrectionStatus::class,
            'requested_by' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
        ];
    }

    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(Scorecard::class);
    }

    public function isApproved(): bool
    {
        return $this->status === CorrectionStatus::Approved;
    }
}
