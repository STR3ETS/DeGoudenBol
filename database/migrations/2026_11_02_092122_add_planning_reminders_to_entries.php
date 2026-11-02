<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Herinneringen voor betaalde inschrijvingen zonder aanlevermoment (besluit Raphael 2 nov 2026): hoeveel er
 * verstuurd zijn en wanneer de laatste, zodat het dashboard na twee herinneringen een taak toont.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->unsignedTinyInteger('planning_reminders')->default(0)->after('scheduled_at');
            $table->timestamp('planning_reminded_at')->nullable()->after('planning_reminders');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->dropColumn(['planning_reminders', 'planning_reminded_at']);
        });
    }
};
