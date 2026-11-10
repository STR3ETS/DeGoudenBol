<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Filmpje als betaalde extra bij de aanmelding (verzoek Gill 10 nov 2026): de bakker vinkt het aan in stap 4,
 * de prijs komt bij het deelnamepakket op dezelfde rekening.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->boolean('video_addon')->default(false)->after('pickup_prepaid');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->dropColumn('video_addon');
        });
    }
};
