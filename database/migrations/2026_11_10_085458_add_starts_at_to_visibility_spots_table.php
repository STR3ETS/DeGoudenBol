<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zichtbaarheidsplekken kunnen later ingaan dan de betaling (besluit Bennie 10 nov 2026: pas na de bekendmaking).
 * Leeg betekent direct vanaf de betaling; zo blijven de plekken die al verkocht zijn zoals ze waren.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visibility_spots', function (Blueprint $table): void {
            $table->timestamp('starts_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('visibility_spots', function (Blueprint $table): void {
            $table->dropColumn('starts_at');
        });
    }
};
