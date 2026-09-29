<?php

namespace App\Domain\Testing\Models;

use App\Domain\Testing\Enums\ScorecardSource;
use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Ingediende scorekaart. Na indienen onveranderlijk: alleen ongeldig verklaren mag,
 * inhoudelijke correcties zijn aparte, goedgekeurde records (ScoreCorrection).
 *
 * @property array<string, int> $scores
 * @property ScorecardSource $source
 */
#[Fillable(['uuid', 'sample_id', 'panelist_id', 'test_session_id', 'scores', 'strengths', 'opportunities', 'submitted_at', 'hash', 'source'])]
class Scorecard extends TestingModel
{
    public const null UPDATED_AT = null;

    /**
     * Kolommen die na indienen nog mogen veranderen (alleen door scorecontrole).
     */
    private const array MUTABLE = ['is_valid', 'invalidated_reason', 'invalidated_by', 'invalidated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'submitted_at' => 'immutable_datetime',
            'source' => ScorecardSource::class,
            'is_valid' => 'boolean',
            'invalidated_by' => 'integer',
            'invalidated_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $card): void {
            $card->created_at ??= now();
            $card->hash = $card->computeHash();
        });

        static::updating(function (self $card): void {
            $illegal = array_diff(array_keys($card->getDirty()), self::MUTABLE);

            if ($illegal !== []) {
                throw new LogicException('Een ingediende scorekaart is onveranderlijk ('.implode(', ', $illegal).').');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Scorekaarten worden nooit verwijderd.');
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(ScoreCorrection::class);
    }

    public function total(): int
    {
        return (int) array_sum($this->scores);
    }

    public function computeHash(): string
    {
        $canonical = json_encode([
            'uuid' => $this->uuid,
            'sample_id' => (int) $this->sample_id,
            'panelist_id' => (int) $this->panelist_id,
            'scores' => $this->sortedScores(),
            'strengths' => $this->strengths,
            'opportunities' => $this->opportunities,
            'submitted_at' => $this->submitted_at?->utc()->format('Y-m-d H:i:s'),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $canonical);
    }

    public function hashMatches(): bool
    {
        return hash_equals($this->computeHash(), (string) $this->hash);
    }

    public function invalidate(string $reason, ?int $byUserId): void
    {
        $this->forceFill([
            'is_valid' => false,
            'invalidated_reason' => $reason,
            'invalidated_by' => $byUserId,
            'invalidated_at' => now(),
        ])->save();
    }

    /**
     * @return array<string, int>
     */
    private function sortedScores(): array
    {
        $scores = array_map('intval', $this->scores ?? []);
        ksort($scores);

        return $scores;
    }
}
