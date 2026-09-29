<?php

namespace App\Domain\Testing\Models;

use App\Domain\Testing\Enums\ResultStatus;
use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Berekende uitslag van een monster. Voorlopig tot scorecontrole hem definitief maakt.
 *
 * @property array<string, float> $criterion_averages
 * @property array<string, mixed> $flags
 * @property ResultStatus $status
 */
#[Fillable(['sample_id', 'scoring_model_version', 'card_count', 'criterion_averages', 'total_raw', 'total', 'flags', 'status', 'computed_at', 'finalized_by', 'finalized_at'])]
class Result extends TestingModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scoring_model_version' => 'integer',
            'card_count' => 'integer',
            'criterion_averages' => 'array',
            'total_raw' => 'float',
            'total' => 'float',
            'flags' => 'array',
            'status' => ResultStatus::class,
            'computed_at' => 'immutable_datetime',
            'finalized_by' => 'integer',
            'finalized_at' => 'immutable_datetime',
        ];
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function isFinal(): bool
    {
        return $this->status === ResultStatus::Final;
    }

    public function formattedTotal(): string
    {
        return number_format($this->total, 1, ',', '.');
    }
}
