<?php

namespace App\Domain\Charities\Actions;

use App\Domain\Charities\Enums\CharityStatus;
use App\Domain\Charities\Exceptions\CharityException;
use App\Domain\Charities\Models\Charity;
use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Statusovergang met checklist en toelichting. Bij goedkeuring gaan de reserveringen van de voordrager
 * uit de regionale pot naar het doel; bij "alternatief" vallen ze terug in de pot.
 */
final class ReviewCharity
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  list<string>|null  $checklist
     */
    public function __invoke(Charity $charity, CharityStatus $to, ?array $checklist = null, ?string $note = null, ?User $by = null): Charity
    {
        if (! in_array($to, $charity->status->allowedTransitions(), true)) {
            throw new CharityException("Van '{$charity->status->getLabel()}' kan niet naar '{$to->getLabel()}'.");
        }

        if ($to === CharityStatus::Approved) {
            $required = array_keys(config('charities.checklist', []));
            $missing = array_diff($required, $checklist ?? []);

            if ($missing !== []) {
                throw new CharityException('Vink alle punten van de beoordelingschecklist af voordat je goedkeurt.');
            }
        }

        return DB::transaction(function () use ($charity, $to, $checklist, $note, $by): Charity {
            $charity->forceFill([
                'status' => $to,
                'review_checklist' => $checklist ?? $charity->review_checklist,
                'review_note' => $note ?? $charity->review_note,
                'reviewed_by' => $by?->getKey() ?? $charity->reviewed_by,
                'reviewed_at' => now(),
            ])->save();

            $this->reassignReservations($charity);
            $this->audit->record('charity.reviewed', $charity, ['status' => $to->value], $by);

            return $charity;
        });
    }

    /**
     * Reserveringen van de voordrager volgen de status: goedgekeurd → naar het doel, anders → pot.
     */
    public function reassignReservations(Charity $charity): void
    {
        if ($charity->nominated_by_type === null) {
            return;
        }

        $payerReservations = CharityReservation::query()
            ->where('edition_id', $charity->edition_id)
            ->where('payer_type', $charity->nominated_by_type)
            ->where('payer_id', $charity->nominated_by_id);

        if ($charity->status->receivesReservations()) {
            $payerReservations->whereNull('charity_id')->update(['charity_id' => $charity->getKey(), 'updated_at' => now()]);

            return;
        }

        CharityReservation::query()->where('charity_id', $charity->getKey())->update(['charity_id' => null, 'updated_at' => now()]);
    }
}
