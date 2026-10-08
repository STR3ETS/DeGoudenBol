<?php

namespace App\Console\Commands;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Medewerkersaccount met rol aanmaken, bedoeld voor de eerste beheerder op een nieuwe omgeving
 * (daarna gaat het via Medewerkers in de backoffice). Werkt ook zonder terminal, via de Plesk-toolkit.
 */
class CreateStaffUserCommand extends Command
{
    protected $signature = 'staff:create
        {email : E-mailadres van de medewerker}
        {name : Naam}
        {--role=admin : Rol: admin, intake, coordinator, reviewer, publisher, communication, voucher_manager of finance}
        {--password= : Wachtwoord; leeg = gegenereerd en eenmalig getoond}';

    protected $description = 'Maakt een actief medewerkersaccount met rol voor de backoffice';

    public function handle(): int
    {
        $role = StaffRole::tryFrom((string) $this->option('role'));

        if ($role === null || $role === StaffRole::Panelist) {
            $this->error('Onbekende rol. Kies uit: '.implode(', ', array_diff(StaffRole::values(), [StaffRole::Panelist->value])).'. Panelleden maak je aan bij Panelleden in de backoffice.');

            return self::FAILURE;
        }

        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("'{$email}' is geen geldig e-mailadres.");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(16, symbols: false));
        Role::findOrCreate($role->value, 'web');

        $user = User::query()->firstOrNew(['email' => $email]);
        $created = ! $user->exists;

        $user->fill(['name' => trim((string) $this->argument('name'))]);
        $user->password = $password;
        $user->is_active = true;
        $user->save();
        $user->assignRole($role->value);

        $this->info(($created ? 'Account aangemaakt' : 'Bestaand account bijgewerkt').": {$email} met rol {$role->getLabel()}.");
        $this->line("Wachtwoord: {$password}");
        $this->line('Bij de eerste keer inloggen op /admin vraagt de backoffice om tweestapsverificatie (authenticator-app).');

        return self::SUCCESS;
    }
}
