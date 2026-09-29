<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Exceptions\ForbiddenRoleCombinationException;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Arr;
use Spatie\Permission\Contracts\Role as RoleContract;

/**
 * Dwingt de verboden rolcombinaties uit het blind protocol af vóór een rol wordt toegekend.
 */
final class RoleAssignmentGuard
{
    /**
     * @param  mixed  ...$roles  Rolnamen, StaffRole-cases, Role-modellen of arrays daarvan (zoals Spatie ze accepteert).
     *
     * @throws ForbiddenRoleCombinationException
     */
    public function assertCanAssign(User $user, mixed ...$roles): void
    {
        $requested = $this->normalize($roles);
        $combined = array_values(array_unique([...$user->staffRoles(), ...$requested], SORT_REGULAR));

        foreach ($combined as $first) {
            foreach ($combined as $second) {
                if ($first !== $second && $first->conflictsWith($second)) {
                    throw ForbiddenRoleCombinationException::for($first, $second);
                }
            }
        }
    }

    /**
     * @param  array<int, mixed>  $roles
     * @return list<StaffRole>
     */
    private function normalize(array $roles): array
    {
        $result = [];

        foreach (Arr::flatten($roles) as $role) {
            $name = match (true) {
                $role instanceof StaffRole => $role->value,
                $role instanceof BackedEnum => (string) $role->value,
                $role instanceof RoleContract => $role->name,
                is_string($role) => $role,
                default => null,
            };

            $staffRole = $name !== null ? StaffRole::tryFrom($name) : null;

            if ($staffRole !== null) {
                $result[] = $staffRole;
            }
        }

        return $result;
    }
}
