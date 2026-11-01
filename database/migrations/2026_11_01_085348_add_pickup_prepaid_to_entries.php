<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ophaalservice al bij de aanmelding betaald (besluit Bennie 1 nov 2026); de vrijdag kiest de bakker later in het portaal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->boolean('pickup_prepaid')->default(false)->after('plain_addon');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->dropColumn('pickup_prepaid');
        });
    }
};
