<?php

namespace App\Domain\Testing\Models;

use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invoer van een papieren scorekaart. Pas als twee mensen onafhankelijk dezelfde
 * scores invoeren ontstaat een Scorecard met bron "paper".
 *
 * @property array<string, int> $scores
 */
#[Fillable(['sample_id', 'panelist_id', 'test_session_id', 'scores', 'strengths', 'opportunities', 'entered_by'])]
class PaperEntry extends TestingModel
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'entered_by' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            $entry->created_at ??= now();
        });
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function panelist(): BelongsTo
    {
        return $this->belongsTo(Panelist::class);
    }

    /**
     * @return array<string, int>
     */
    public function normalizedScores(): array
    {
        $scores = array_map('intval', $this->scores ?? []);
        ksort($scores);

        return $scores;
    }

    public function matches(self $other): bool
    {
        return $this->normalizedScores() === $other->normalizedScores();
    }
}
