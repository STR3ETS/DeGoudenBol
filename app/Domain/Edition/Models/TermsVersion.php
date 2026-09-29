<?php

namespace App\Domain\Edition\Models;

use App\Domain\Edition\Enums\TermsType;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Een versie van voorwaarden of privacyverklaring. Akkoorden verwijzen altijd naar een versie.
 * De teksten komen van de jurist; wij bouwen alleen de plek waar ze landen.
 */
#[Fillable(['edition_id', 'type', 'version', 'title', 'body', 'published_at'])]
class TermsVersion extends DomainModel
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TermsType::class,
            'published_at' => 'immutable_datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public static function latestPublished(TermsType $type, ?Edition $edition = null): ?self
    {
        return static::query()
            ->published()
            ->where('type', $type)
            ->when($edition, fn (Builder $query) => $query->where('edition_id', $edition->id))
            ->orderByDesc('published_at')
            ->first();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }
}
