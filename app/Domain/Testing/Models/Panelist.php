<?php

namespace App\Domain\Testing\Models;

use App\Domain\Testing\Enums\PoolStatus;
use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Panellid binnen de testketen. Wordt in de app en op kaarten alleen met de weergavecode (P07) genoemd.
 *
 * @property PoolStatus $pool_status
 * @property list<string>|null $allergens
 */
#[Fillable(['user_id', 'edition_id', 'display_code', 'pool_status', 'fee_per_session_cents', 'allergens', 'consent_at', 'notes'])]
class Panelist extends TestingModel
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'edition_id' => 'integer',
            'pool_status' => PoolStatus::class,
            'fee_per_session_cents' => 'integer',
            'allergens' => 'array',
            'consent_at' => 'immutable_datetime',
        ];
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(TestSession::class, 'session_panelists')->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ServingAssignment::class);
    }

    public function scorecards(): HasMany
    {
        return $this->hasMany(Scorecard::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('pool_status', PoolStatus::Active);
    }

    /**
     * Eerstvolgende vrije code: P01, P02, …
     */
    public static function nextDisplayCode(): string
    {
        $taken = static::query()->pluck('display_code')->all();

        for ($i = 1; $i < 1000; $i++) {
            $code = sprintf('P%02d', $i);

            if (! in_array($code, $taken, true)) {
                return $code;
            }
        }

        return 'P'.random_int(1000, 9999);
    }
}
