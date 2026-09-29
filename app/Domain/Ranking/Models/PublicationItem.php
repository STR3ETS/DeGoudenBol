<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Enums\ItemVisibility;
use App\Domain\Testing\Enums\SampleRound;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén te publiceren uitslag: naam, provincie, ronde, cijfer en weergave. Bevat nooit het testnummer.
 *
 * @property array{total: float, total_raw: float, card_count: int, criterion_averages: array<string, float>, scoring_model_version: int} $result_snapshot
 * @property ItemVisibility $visibility
 * @property SampleRound $round
 */
#[Fillable(['publication_batch_id', 'entry_id', 'province_id', 'round', 'result_snapshot', 'visibility'])]
class PublicationItem extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_snapshot' => 'array',
            'visibility' => ItemVisibility::class,
            'round' => SampleRound::class,
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PublicationBatch::class, 'publication_batch_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function total(): float
    {
        return (float) ($this->result_snapshot['total'] ?? 0);
    }

    public function isPublic(): bool
    {
        return $this->visibility === ItemVisibility::Public;
    }

    public function isFinal(): bool
    {
        return $this->round === SampleRound::Final;
    }
}
