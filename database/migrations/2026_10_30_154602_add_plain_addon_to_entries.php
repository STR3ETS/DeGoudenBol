<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extra test van de naturel oliebol, bij de aanmelding gekozen (openingsactie, besluit Bennie 30 okt 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->boolean('plain_addon')->default(false)->after('rate');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->dropColumn('plain_addon');
        });
    }
};
