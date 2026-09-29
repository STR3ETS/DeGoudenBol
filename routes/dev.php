<?php

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Testing\Enums\PoolStatus;
use App\Domain\Testing\Models\Panelist;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Alleen lokaal: inloggen op de backoffice zonder wachtwoord en MFA-challenge,
| voor screenshots en snel testen. Wordt uitsluitend geladen bij APP_ENV=local.
| Met ?als=e-mailadres log je in als een bestaand lokaal account (bijvoorbeeld
| een gezaaid panellid); zonder ?als ontstaat screenshot-{rol}@degoudenbol.test.
|--------------------------------------------------------------------------
*/

Route::get('/dev/inloggen-als/{role}', function (string $role) {
    abort_unless(app()->environment('local') && config('app.debug'), 404);

    $staffRole = StaffRole::tryFrom($role) ?? abort(404);

    $user = request()->filled('als')
        ? User::query()->where('email', request('als'))->firstOrFail()
        : User::query()->firstOrCreate(
            ['email' => "screenshot-{$staffRole->value}@degoudenbol.test"],
            ['name' => 'Screenshot '.$staffRole->getLabel(), 'password' => 'password', 'is_active' => true],
        );

    if (! $user->hasRole($staffRole->value)) {
        $user->assignRole($staffRole->value);
    }

    // Een vaste (nep)sleutel zodat de MFA-verplichting niet naar de setup stuurt.
    if (blank($user->app_authentication_secret)) {
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    }

    // Een panellid heeft een plek in de panelpool nodig om de panel-app te kunnen openen.
    if ($staffRole === StaffRole::Panelist) {
        Panelist::query()->firstOrCreate(
            ['user_id' => $user->getKey()],
            ['display_code' => Panelist::nextDisplayCode(), 'pool_status' => PoolStatus::Active, 'allergens' => [], 'consent_at' => now()],
        );
    }

    Auth::guard('web')->login($user);
    request()->session()->regenerate();

    return redirect($staffRole === StaffRole::Panelist ? '/panel' : '/admin');
})->name('dev.inloggen-als');

// Alleen lokaal: inloggen op het portaal als een bestaand deelnemersaccount, zonder inloglink.
Route::get('/dev/portaal-als/{email}', function (string $email) {
    abort_unless(app()->environment('local') && config('app.debug'), 404);

    $user = ParticipantUser::query()->where('email', $email)->firstOrFail();

    Auth::guard('participant')->login($user);
    request()->session()->regenerate();

    return redirect('/portaal');
})->name('dev.portaal-als');
