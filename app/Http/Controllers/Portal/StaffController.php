<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Notifications\MagicLinkNotification;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Extra accounts voor een bedrijf, bijvoorbeeld alleen om cadeaubonnen te scannen. Alleen voor eigenaren.
 */
class StaffController extends Controller
{
    public function index(Request $request, CompanyAccess $access, ?string $bedrijf = null): View
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner]);

        return view('portal.medewerkers.index', [
            'company' => $company,
            'members' => $company->users()->orderBy('name')->get(),
            'roles' => CompanyUserRole::cases(),
        ]);
    }

    public function store(Request $request, CompanyAccess $access, string $bedrijf): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::enum(CompanyUserRole::class)],
        ], [], ['name' => 'naam', 'email' => 'e-mailadres', 'role' => 'rol']);

        $user = ParticipantUser::query()->firstOrCreate(
            ['email' => Str::lower($data['email'])],
            ['name' => $data['name']],
        );

        if ($company->users()->whereKey($user->getKey())->exists()) {
            return back()->withErrors(['email' => 'Dit account is al gekoppeld aan dit bedrijf.']);
        }

        $company->users()->attach($user->getKey(), ['role' => $data['role']]);
        $user->notify(new MagicLinkNotification(route('portaal.dashboard')));

        return back()->with('status', "{$user->name} is toegevoegd en heeft een inloglink ontvangen.");
    }

    public function destroy(Request $request, CompanyAccess $access, string $bedrijf, ParticipantUser $user): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner]);

        $member = $company->users()->whereKey($user->getKey())->first();

        abort_if($member === null, 404);

        if ($member->pivot->role === CompanyUserRole::Owner && $this->ownerCount($company) <= 1) {
            return back()->withErrors(['email' => 'Een bedrijf houdt altijd minstens één eigenaar.']);
        }

        $company->users()->detach($user->getKey());

        return back()->with('status', "{$user->name} heeft geen toegang meer tot {$company->name}.");
    }

    private function ownerCount(Company $company): int
    {
        return $company->users()->wherePivot('role', CompanyUserRole::Owner->value)->count();
    }
}
