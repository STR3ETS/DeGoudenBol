<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('entries:cancel-expired')->everyFiveMinutes();

// Publicatieritme (di/vr 12:00) en dagelijkse herberekening van de lijsten (docs/04 §3 en §4).
Schedule::command('publication:run')->everyMinute()->withoutOverlapping();
Schedule::command('ranking:verify')->dailyAt('06:00');
Schedule::command('vouchers:tick')->hourly()->withoutOverlapping();

// Versheid: een half uur voor het verlopen een melding in de bel voor coördinatie en scorecontrole.
Schedule::command('freshness:notify')->everyTenMinutes()->withoutOverlapping();
