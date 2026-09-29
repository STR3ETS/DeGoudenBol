<?php

namespace App\Domain\Testing\Models;

use App\Domain\Testing\Enums\AssignmentStatus;
use App\Support\Models\TestingModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén regel uit het uitserveerschema: dit panellid krijgt dit monster als zoveelste.
 *
 * @property AssignmentStatus $status
 */
#[Fillable(['test_session_id', 'panelist_id', 'sample_id', 'serving_order', 'status'])]
class ServingAssignment extends TestingModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'serving_order' => 'integer',
            'status' => AssignmentStatus::class,
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

    /**
     * De kaart van dit panellid voor dit monster (samengestelde sleutel, dus geen relatie).
     */
    public function findScorecard(): ?Scorecard
    {
        return Scorecard::query()->where('sample_id', $this->sample_id)->where('panelist_id', $this->panelist_id)->first();
    }
}
