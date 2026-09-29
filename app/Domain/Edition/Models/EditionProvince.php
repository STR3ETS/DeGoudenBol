<?php

namespace App\Domain\Edition\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Koppeling editie <-> provincie met capaciteit en het reveal-moment op 21 december.
 */
class EditionProvince extends Pivot
{
    protected $table = 'edition_province';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'reveal_at' => 'immutable_datetime',
            'revealed_at' => 'immutable_datetime',
        ];
    }
}
