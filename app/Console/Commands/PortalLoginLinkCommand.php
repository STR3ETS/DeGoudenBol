<?php

namespace App\Console\Commands;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\MagicLinkService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Inloglink (en optioneel een wachtwoord) voor een bakker, zonder mail. Voor omgevingen waar de mail
 * nog niet werkt of om iemand te helpen die geen mail ontvangt. Werkt ook via de Plesk-toolkit.
 */
class PortalLoginLinkCommand extends Command
{
    protected $signature = 'portal:login-link
        {email : E-mailadres van het deelnemersaccount}
        {--minutes=60 : Geldigheid van de link in minuten}
        {--password= : Zet dit wachtwoord op het account (leeg = alleen een link)}';

    protected $description = 'Drukt een inloglink voor het deelnemersportaal af en zet desgewenst een wachtwoord';

    public function handle(MagicLinkService $magicLinks): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $user = ParticipantUser::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("Geen deelnemersaccount met e-mailadres '{$email}'.");
            $this->line('Accounts ontstaan bij het aanmelden van een bedrijf of via Medewerkers in het portaal.');

            return self::FAILURE;
        }

        $minutes = max(1, (int) $this->option('minutes'));
        $url = $magicLinks->url($user, $minutes);

        $this->info("Inloglink voor {$user->name} ({$email}), {$minutes} minuten geldig en eenmalig:");
        $this->line($url);

        if (filled($this->option('password'))) {
            $user->password = (string) $this->option('password');
            $user->save();
            $this->line('Wachtwoord ingesteld; inloggen kan ook met e-mail en wachtwoord op /portaal/inloggen.');
        }

        return self::SUCCESS;
    }
}
