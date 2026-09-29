<?php

namespace App\Filament\Concerns;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;

/**
 * Zichtbaarheid van een resource per medewerkersrol. De beheerder ziet alles,
 * maar mag nooit scores wijzigen (dat regelen de testketen-resources apart).
 */
trait RestrictsToRoles
{
    /**
     * @return list<StaffRole>
     */
    abstract protected static function allowedRoles(): array;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole(StaffRole::Admin->value)) {
            return true;
        }

        return $user->hasAnyRole(array_map(fn (StaffRole $role) => $role->value, static::allowedRoles()));
    }
}
