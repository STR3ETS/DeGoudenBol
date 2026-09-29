<?php

namespace App\Domain\Ranking\Models;

use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Goedkeuring van een batch of correctiedossier. Twee verschillende personen, nooit de indiener.
 */
#[Fillable(['user_id', 'approved_at'])]
class Approval extends DomainModel
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Een goedkeuring wordt nooit gewijzigd.');
        });
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
