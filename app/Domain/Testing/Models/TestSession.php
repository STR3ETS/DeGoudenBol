<?php

namespace App\Domain\Testing\Models;

use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SessionStatus;
use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Testsessie: een groep monsters die door een groep panelleden op één moment beoordeeld wordt.
 *
 * @property SampleRound $round
 * @property SessionStatus $status
 */
#[Fillable(['edition_id', 'test_location_id', 'round', 'name', 'starts_at', 'ends_at', 'status', 'max_samples', 'schedule_generated_at', 'notes'])]
class TestSession extends TestingModel
{
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edition_id' => 'integer',
            'test_location_id' => 'integer',
            'round' => SampleRound::class,
            'status' => SessionStatus::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'max_samples' => 'integer',
            'schedule_generated_at' => 'immutable_datetime',
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

    public function samples(): BelongsToMany
    {
        return $this->belongsToMany(Sample::class, 'session_samples')
            ->withPivot('serving_order')
            ->withTimestamps()
            ->orderByPivot('serving_order');
    }

    public function panelists(): BelongsToMany
    {
        return $this->belongsToMany(Panelist::class, 'session_panelists')->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ServingAssignment::class);
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(ServingExclusion::class);
    }

    public function scorecards(): HasMany
    {
        return $this->hasMany(Scorecard::class);
    }

    public function displayName(): string
    {
        return $this->name ?: 'Sessie '.$this->starts_at->format('d-m H:i');
    }
}
