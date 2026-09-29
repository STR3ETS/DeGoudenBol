<?php

namespace App\Domain\Vault\Models;

use App\Support\Models\VaultModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use LogicException;

/**
 * De enige plek waar testnummer en inschrijving elkaar raken. Alleen via VaultService.
 */
#[Fillable(['sample_id', 'edition_id', 'entry_ref', 'entry_lookup', 'created_by'])]
class VaultLink extends VaultModel
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sample_id' => 'integer',
            'edition_id' => 'integer',
            'created_by' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $link): void {
            $link->created_at ??= now();
        });

        static::updating(function (): never {
            throw new LogicException('Een kluiskoppeling wordt nooit gewijzigd.');
        });
    }
}
