<?php

namespace App\Domain\Marketing\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Marketing\Enums\Milestone;
use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Erkenning met onraadbare verificatiecode (/erkenning/{code}). Hangt aan de ranking, nooit aan een pakket.
 *
 * @property RecognitionType $type
 */
#[Fillable(['code', 'entry_id', 'company_id', 'edition_id', 'province_id', 'type', 'status', 'valid_from', 'valid_until', 'embargo_until'])]
class Recognition extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RecognitionType::class,
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'embargo_until' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $recognition): void {
            $recognition->code ??= self::generateCode();
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'active')->where(fn (Builder $q) => $q->whereNull('embargo_until')->orWhere('embargo_until', '<=', now()));
    }

    public function isEmbargoed(): bool
    {
        return $this->embargo_until !== null && $this->embargo_until->isFuture();
    }

    public function isHistoric(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ! $this->isEmbargoed() && ! $this->isHistoric();
    }

    public function badgeText(): string
    {
        return $this->type->badgeText($this->edition->year, $this->province?->name);
    }

    public function milestone(): Milestone
    {
        return Milestone::fromRecognition($this->type);
    }

    public function verificationUrl(): string
    {
        return route('erkenning.toon', $this);
    }

    public function badgeUrl(string $variant = 'licht'): string
    {
        return route('erkenning.badge', ['recognition' => $this, 'variant' => $variant]);
    }

    /**
     * Embedcode: de badge laadt vanaf ons platform en linkt naar de verificatiepagina (backlink).
     */
    public function embedCode(string $variant = 'licht'): string
    {
        $alt = e($this->badgeText());

        return '<a href="'.$this->verificationUrl().'" target="_blank" rel="noopener"><img src="'.$this->badgeUrl($variant).'" alt="'.$alt.'" width="300" height="100" loading="lazy"></a>';
    }
}
