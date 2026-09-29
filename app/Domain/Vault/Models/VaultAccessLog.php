<?php

namespace App\Domain\Vault\Models;

use App\Support\Models\VaultModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use LogicException;

/**
 * Append-only inzagelog van de kluis.
 */
#[Fillable(['user_id', 'actor', 'action', 'sample_id', 'entry_lookup', 'reason', 'ip'])]
class VaultAccessLog extends VaultModel
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'sample_id' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $log): void {
            $log->created_at ??= now();
        });

        static::updating(function (): never {
            throw new LogicException('De kluislog is append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('De kluislog is append-only.');
        });
    }
}
