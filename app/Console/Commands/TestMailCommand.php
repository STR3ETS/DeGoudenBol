<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Stuurt direct (zonder wachtrij) een testmail en toont de mailconfiguratie en de stand van de queue.
 * Bedoeld voor Plesk zonder terminal: één commando, één duidelijke uitkomst.
 */
class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email : Ontvanger van de testmail}';

    protected $description = 'Verstuurt een testmail buiten de wachtrij om en rapporteert mail- en queue-instellingen';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');

        $this->line('Mailer: '.$mailer);
        $this->line('Afzender: '.config('mail.from.address').' ('.config('mail.from.name').')');

        if ($mailer === 'mailgun') {
            $this->line('Mailgun-domein: '.(config('services.mailgun.domain') ?: '(leeg!)').' · endpoint: '.config('services.mailgun.endpoint').' · secret: '.(filled(config('services.mailgun.secret')) ? 'ingevuld' : '(leeg!)'));
        }

        $this->line('Queue: '.config('queue.default'));

        if (config('queue.default') === 'database' && Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')->count();
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
            $this->line("Wachtrij: {$pending} wachtend, {$failed} mislukt");

            if ($pending > 0) {
                $this->warn("Er staan {$pending} taken (mails) te wachten: de queue-worker draait niet. Start `php artisan queue:work --tries=3` als achtergrondproces, of draai eenmalig `queue:work --stop-when-empty`.");
            }

            if ($failed > 0) {
                $last = DB::table('failed_jobs')->orderByDesc('id')->value('exception');
                $this->warn('Laatste mislukte taak: '.strtok((string) $last, "\n"));
            }
        }

        $email = (string) $this->argument('email');

        try {
            Mail::raw('Testmail van De Gouden Bol. Als je dit leest, werkt de mailconfiguratie.', fn ($message) => $message->to($email)->subject('Testmail De Gouden Bol'));
            $this->info("Testmail verstuurd naar {$email} via '{$mailer}'. Controleer de inbox (en spam) en het Mailgun-logboek.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Versturen mislukt: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
