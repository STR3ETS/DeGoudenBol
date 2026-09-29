<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Edition\Enums\FinalistFallback;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Enums\FinalistStatus;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\RankingSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Bevestigen of afmelden voor de finale. Bij afmelding gaat de plek naar nummer 2 van de bevroren
 * lijst (besluit 17, instelling finalist_fallback); de provinciale titel blijft bij nummer 1.
 */
final class RespondToFinalInvitation
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(Finalist $finalist, bool $accepts, ?Model $actor = null): Finalist
    {
        if (! $finalist->status->participates()) {
            throw new LogicException('Deze finale-uitnodiging is al afgehandeld.');
        }

        return DB::transaction(function () use ($finalist, $accepts, $actor): Finalist {
            $finalist->forceFill([
                'status' => $accepts ? FinalistStatus::Confirmed : FinalistStatus::Declined,
                'responded_at' => now(),
            ])->save();

            $this->audit->record($accepts ? 'finalist.confirmed' : 'finalist.declined', $finalist, ['province' => $finalist->province_id], $actor);

            if ($accepts) {
                return $finalist;
            }

            $edition = $finalist->edition;

            if ($edition->settings->finalistFallback !== FinalistFallback::NextInLine) {
                return $finalist;
            }

            $snapshot = RankingSnapshot::frozenForProvince($edition->getKey(), $finalist->province_id);
            $next = $snapshot?->positions()->where('position', '>', $finalist->province_position)->orderBy('position')->first();

            if ($next === null || Finalist::query()->where('edition_id', $edition->getKey())->where('entry_id', $next->entry_id)->exists()) {
                return $finalist;
            }

            $replacement = Finalist::query()->create([
                'edition_id' => $edition->getKey(),
                'province_id' => $finalist->province_id,
                'entry_id' => $next->entry_id,
                'origin' => 'next_in_line',
                'province_position' => $next->position,
                'status' => FinalistStatus::Invited,
                'invited_at' => now(),
            ]);

            $finalist->forceFill(['replaced_by_id' => $replacement->getKey()])->save();

            $this->audit->record('finalist.replaced', $replacement, ['replaces' => $finalist->getKey()], $actor);

            return $finalist;
        });
    }
}
