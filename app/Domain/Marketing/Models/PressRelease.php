<?php

namespace App\Domain\Marketing\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Marketing\Enums\PressMilestone;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Persbericht per provincie (of landelijk), gegenereerd bij de bevriezing en onder embargo tot de reveal.
 * Communicatie redigeert de tekst; de perskit gaat via een tijdelijke ondertekende link.
 *
 * @property PressMilestone $milestone
 */
#[Fillable(['edition_id', 'province_id', 'milestone', 'title', 'slug', 'body', 'embargo_until', 'published_at', 'sent_at', 'generated_at', 'updated_by'])]
class PressRelease extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'milestone' => PressMilestone::class,
            'embargo_until' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'generated_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $release): void {
            if (blank($release->slug)) {
                $release->slug = static::uniqueSlug($release->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'persbericht';
        $slug = $base;
        $counter = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function isEmbargoed(): bool
    {
        return ! $this->isPublished() && $this->embargo_until !== null && $this->embargo_until->isFuture();
    }

    public function scopeLabel(): string
    {
        return $this->province?->name ?? 'Landelijk';
    }
}
