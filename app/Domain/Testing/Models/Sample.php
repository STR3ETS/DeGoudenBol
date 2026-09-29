<?php

namespace App\Domain\Testing\Models;

use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SampleStatus;
use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Eén aangeleverd monster. Kent alleen zijn testnummer; de deelnemer staat uitsluitend in de kluis.
 *
 * @property SampleRound $round
 * @property SampleStatus $status
 */
#[Fillable(['edition_id', 'round', 'sample_number', 'status', 'test_location_id', 'notes'])]
class Sample extends TestingModel
{
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edition_id' => 'integer',
            'round' => SampleRound::class,
            'status' => SampleStatus::class,
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function intake(): HasOne
    {
        return $this->hasOne(Intake::class);
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(TestSession::class, 'session_samples')
            ->withPivot('serving_order')
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ServingAssignment::class);
    }

    public function scorecards(): HasMany
    {
        return $this->hasMany(Scorecard::class);
    }

    public function validScorecards(): HasMany
    {
        return $this->scorecards()->where('is_valid', true);
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForEdition(Builder $query, int $editionId): Builder
    {
        return $query->where('edition_id', $editionId);
    }

    public function label(): string
    {
        return $this->sample_number ?? 'zonder nummer';
    }

    public function isFresh(): bool
    {
        $expiresAt = $this->intake?->freshness_expires_at;

        return $expiresAt !== null && $expiresAt->isFuture();
    }
}
