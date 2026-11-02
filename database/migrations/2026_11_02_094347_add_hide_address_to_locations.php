<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Huisadres in plaats van een kraam- of winkeladres: dan toont het openbare profiel alleen de plaats,
 * zonder straat, routelink of coördinaten (besluit Raphael 2 nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->boolean('hide_address')->default(false)->after('is_primary');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn('hide_address');
        });
    }
};
