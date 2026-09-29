<?php

namespace App\Domain\Testing\Models;

use App\Domain\Testing\Enums\ExclusionReason;
use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uitsluiting van een panellid voor een monster, met alleen een reden-code.
 *
 * @property ExclusionReason $reason_code
 */
#[Fillable(['test_session_id', 'panelist_id', 'sample_id', 'reason_code'])]
class ServingExclusion extends TestingModel
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason_code' => ExclusionReason::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }

    public function panelist(): BelongsTo
    {
        return $this->belongsTo(Panelist::class);
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }
}
