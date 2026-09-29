<?php

namespace App\Domain\Participants\Services;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\ParticipantUser;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Bepaalt met welk bedrijf een deelnemer in het portaal werkt en of de rol dat toestaat.
 */
final class CompanyAccess
{
    /**
     * @param  list<CompanyUserRole>  $roles  Toegestane rollen; leeg = elke rol.
     *
     * @throws HttpException
     */
    public function resolve(ParticipantUser $user, ?string $slug = null, array $roles = []): Company
    {
        $query = $user->companies()->active()->orderBy('companies.name');

        $company = $slug !== null
            ? $query->where('companies.slug', $slug)->first()
            : $query->first();

        if ($company === null) {
            abort($slug !== null ? 404 : 403, 'Aan dit account is geen bedrijf gekoppeld.');
        }

        $role = $company->pivot->role;

        if ($roles !== [] && ! in_array($role, $roles, true)) {
            abort(403, 'Uw rol binnen dit bedrijf geeft hier geen toegang toe.');
        }

        return $company;
    }

    public function roleOf(ParticipantUser $user, Company $company): ?CompanyUserRole
    {
        return $user->roleIn($company);
    }
}
