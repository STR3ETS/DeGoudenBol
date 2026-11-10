<?php

namespace App\Domain\Participants\Services;

use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

/**
 * Meekijken in het portaal van een bakker (verzoek Gill, 10 nov 2026): een medewerker opent het portaal
 * zoals de bakker het ziet. Alleen lezen, alleen voor rollen die de deelnemer al bij naam kennen, beperkt
 * in tijd, en elke inzage komt in de auditlog. Rapport en uitslag blijven dicht: de koppeling tussen een
 * deelnemer en zijn scores hoort niet via deze weg zichtbaar te worden.
 */
final class PortalViewing
{
    public const string SESSION_KEY = 'portal_viewing';

    public const int MINUTES = 60;

    /**
     * Portaalpagina's die tijdens het meekijken dicht blijven.
     *
     * @var list<string>
     */
    public const array CLOSED_ROUTES = ['portaal.rapport', 'portaal.oorkonde', 'portaal.uitslag'];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return list<StaffRole>
     */
    public static function allowedRoles(): array
    {
        return [StaffRole::Admin, StaffRole::Intake, StaffRole::Finance];
    }

    public static function allows(mixed $staff): bool
    {
        return $staff instanceof User
            && $staff->is_active
            && $staff->hasAnyRole(array_map(fn (StaffRole $role) => $role->value, self::allowedRoles()));
    }

    /**
     * Het account waarmee het portaal van dit bedrijf wordt geopend: de eigenaar, anders het eerste account.
     */
    public function accountFor(Company $company): ?ParticipantUser
    {
        return $company->owners()->first() ?? $company->users()->orderBy('participant_users.id')->first();
    }

    /**
     * Start het meekijken. Bewust zonder "onthoud mij" en zonder de laatste login van de bakker bij te werken.
     *
     * @throws AuthorizationException
     */
    public function start(User $staff, Company $company, ParticipantUser $account): void
    {
        if (! self::allows($staff)) {
            throw new AuthorizationException('Deze rol mag niet meekijken in het portaal.');
        }

        if (! $account->companies()->whereKey($company->getKey())->exists()) {
            throw new AuthorizationException('Dit account hoort niet bij dit bedrijf.');
        }

        Auth::guard('participant')->login($account);

        session()->put(self::SESSION_KEY, [
            'staff_id' => $staff->getKey(),
            'participant_user_id' => $account->getKey(),
            'company_id' => $company->getKey(),
            'company_name' => $company->name,
            'expires_at' => now()->addMinutes(self::MINUTES)->getTimestamp(),
        ]);

        $this->audit->record('portal.viewing_started', $company, [
            'participant_user_id' => $account->getKey(),
            'minutes' => self::MINUTES,
        ], $staff);
    }

    /**
     * De lopende meekijksessie, of null als er geen (geldige) is.
     *
     * @return array{staff_id: int, participant_user_id: int, company_id: int, company_name: string, expires_at: int}|null
     */
    public function current(): ?array
    {
        $state = session()->get(self::SESSION_KEY);

        if (! is_array($state)) {
            return null;
        }

        $staff = Auth::guard('web')->user();

        $valid = self::allows($staff)
            && (int) $staff->getKey() === (int) ($state['staff_id'] ?? 0)
            && (int) Auth::guard('participant')->id() === (int) ($state['participant_user_id'] ?? 0)
            && (int) ($state['expires_at'] ?? 0) > now()->getTimestamp();

        return $valid ? $state : null;
    }

    /**
     * Is deze sessie (ooit) als meekijksessie gestart? Dan mag er niets worden gewijzigd, ook niet als ze verlopen is.
     */
    public function blocksChanges(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /**
     * Beëindigt het meekijken. De medewerker blijft ingelogd in de backoffice; de "onthoud mij" van de bakker
     * op zijn eigen apparaten blijft geldig (daarom geen gewone logout, die het token vernieuwt).
     */
    public function stop(): ?Company
    {
        $state = session()->pull(self::SESSION_KEY);

        if (! is_array($state)) {
            return null;
        }

        Auth::guard('participant')->logoutCurrentDevice();

        return Company::query()->find($state['company_id'] ?? null);
    }
}
