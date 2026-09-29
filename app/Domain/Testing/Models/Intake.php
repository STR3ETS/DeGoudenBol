<?php

namespace App\Domain\Testing\Models;

use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ontvangstregistratie van een monster: tijd, temperatuur, stuks, foto en de versheidsklok.
 */
#[Fillable([
    'sample_id',
    'received_at',
    'temperature_c',
    'piece_count',
    'photo_path',
    'received_by',
    'freshness_expires_at',
    'label_printed_at',
    'notes',
])]
class Intake extends TestingModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'immutable_datetime',
            'temperature_c' => 'float',
            'piece_count' => 'integer',
            'received_by' => 'integer',
            'freshness_expires_at' => 'immutable_datetime',
            'label_printed_at' => 'immutable_datetime',
        ];
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function isFresh(): bool
    {
        return $this->freshness_expires_at->isFuture();
    }
}
