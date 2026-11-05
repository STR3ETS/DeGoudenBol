<?php

namespace App\Domain\Participants\Actions;

use App\Domain\Commerce\Models\Order;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Test- of mislukte aanmelding definitief weghalen (verzoek Gill en Martijn 4 nov 2026). Alleen als er niets
 * is betaald en de inschrijving niet in de test zit; facturen en de 10%-reservering blijven zo altijd kloppen.
 * Het bedrijf en het portaalaccount gaan mee als ze nergens anders bij horen. De audit-log houdt vast wie het deed.
 */
final class DeleteUnpaidEntry
{
    public function __construct(private readonly AuditLogger $audit) {}

    public static function canDelete(Entry $entry): bool
    {
        $paid = Order::query()
            ->where('orderable_type', $entry->getMorphClass())
            ->where('orderable_id', $entry->getKey())
            ->whereNotNull('paid_at')
            ->exists();

        return ! $paid && in_array($entry->status, [EntryStatus::PendingPayment, EntryStatus::Cancelled, EntryStatus::Withdrawn], true);
    }

    public function __invoke(Entry $entry, User $by): void
    {
        if (! self::canDelete($entry)) {
            throw new InvalidArgumentException("{$entry->public_name} is betaald of zit al in de test en kan niet worden verwijderd.");
        }

        DB::transaction(function () use ($entry, $by): void {
            $company = $entry->company;
            $userIds = $company?->users()->pluck('participant_users.id') ?? collect();

            $this->audit->record('entry.deleted', $entry, ['by' => $by->email, 'name' => $entry->public_name, 'company' => $company?->name]);

            Order::query()->where('orderable_type', $entry->getMorphClass())->where('orderable_id', $entry->getKey())->get()->each->delete();
            $entry->pickupRequest?->order?->delete();
            $entry->delete();

            if ($company !== null && ! $company->entries()->exists()) {
                $company->delete();

                ParticipantUser::query()
                    ->whereIn('id', $userIds)
                    ->whereDoesntHave('companies')
                    ->get()
                    ->each->delete();
            }
        });
    }
}
