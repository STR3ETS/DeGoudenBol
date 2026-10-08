<?php

namespace App\Support\Database;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Facades\Schema;

/**
 * `migrate:fresh` wist alleen de standaardconnectie; de testketen en de kluis hebben eigen databases.
 * Zodra een `migrate:fresh` daadwerkelijk gaat migreren (dus ná de productiebevestiging), worden ook
 * die connecties leeggemaakt, zodat hun migraties opnieuw kunnen draaien. Een gewone `migrate` raakt niets.
 */
final class FreshSecondaryConnections
{
    public const array CONNECTIONS = ['testing', 'vault'];

    private static bool $freshRequested = false;

    public static function commandStarting(CommandStarting $event): void
    {
        self::$freshRequested = $event->command === 'migrate:fresh';
    }

    public static function migrationsStarted(MigrationsStarted $event): void
    {
        if (! self::$freshRequested || $event->method !== 'up') {
            return;
        }

        self::$freshRequested = false;

        foreach (self::CONNECTIONS as $connection) {
            if (config("database.connections.{$connection}") !== null) {
                Schema::connection($connection)->dropAllTables();
            }
        }
    }
}
