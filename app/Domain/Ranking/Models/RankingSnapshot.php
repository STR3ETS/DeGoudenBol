<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Ranking\Enums\SnapshotStatus;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Berekende lijst (Voorlijst) per provincie of landelijk. Met input_hash achteraf na te rekenen.
 * Een bevroren snapshot krijgt pas een published_at bij de reveal op de publicatiedag.
 *
 * @property SnapshotStatus $status
 */
#[Fillable(['edition_id', 'province_id', 'scope', 'round', 'engine_version', 'status', 'input_hash', 'publication_batch_id', 'computed_at', 'published_at'])]
class RankingSnapshot extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SnapshotStatus::class,
            'computed_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PublicationBatch::class, 'publication_batch_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(RankingPosition::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForProvince(Builder $query, int $editionId, int $provinceId): Builder
    {
        return $query->where('edition_id', $editionId)->where('scope', "province:{$provinceId}");
    }

    /**
     * De lijst die het publiek nu ziet: de laatst gepubliceerde (of onthulde) snapshot.
     */
    public static function latestForProvince(int $editionId, int $provinceId): ?self
    {
        return static::query()->forProvince($editionId, $provinceId)->whereNotNull('published_at')->orderByDesc('published_at')->orderByDesc('id')->first();
    }

    /**
     * De bevroren lijst van een provincie, ook als die nog onder embargo staat.
     */
    public static function frozenForProvince(int $editionId, int $provinceId): ?self
    {
        return static::query()->forProvince($editionId, $provinceId)->where('status', SnapshotStatus::Frozen)->orderByDesc('id')->first();
    }

    public static function latestNational(int $editionId): ?self
    {
        return static::query()->where('edition_id', $editionId)->where('scope', 'national')->whereNotNull('published_at')->orderByDesc('published_at')->orderByDesc('id')->first();
    }

    public function isFrozen(): bool
    {
        return $this->status === SnapshotStatus::Frozen;
    }

    public function isRevealed(): bool
    {
        return $this->published_at !== null;
    }
}
