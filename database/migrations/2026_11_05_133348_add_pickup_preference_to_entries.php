<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ophaalvrijdag en ophaallocatie al bij de aanmelding (verzoek Gill 4 nov 2026: kramen staan vaak bij een
 * bouwmarkt of tuincentrum). Na de betaling wordt hiermee automatisch de ophaalaanvraag gemaakt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->json('pickup_preference')->nullable()->after('pickup_prepaid');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->dropColumn('pickup_preference');
        });
    }
};
