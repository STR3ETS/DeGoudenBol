<?php

namespace App\Domain\Platform\Models;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Append-only audittrail. Iedere regel bevat de hash van de vorige; wijzigen of
 * verwijderen valt direct op bij `php artisan audit:verify`.
 */
#[Fillable(['actor_type', 'actor_id', 'action', 'subject_type', 'subject_id', 'payload', 'ip'])]
class AuditLog extends DomainModel
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('De auditlog is append-only: regels kunnen niet worden gewijzigd.');
        });

        static::deleting(function (): never {
            throw new LogicException('De auditlog is append-only: regels kunnen niet worden verwijderd.');
        });
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
