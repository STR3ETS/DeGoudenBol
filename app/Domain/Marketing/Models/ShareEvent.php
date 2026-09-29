<?php

namespace App\Domain\Marketing\Models;

use App\Domain\Marketing\Enums\Milestone;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * Meting van een download of deelklik. Alleen voor evaluatie; nooit input voor scores.
 */
#[Fillable(['entry_id', 'company_id', 'milestone', 'kind', 'format'])]
class ShareEvent extends DomainModel
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'milestone' => Milestone::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            $event->created_at ??= now();
        });
    }
}
