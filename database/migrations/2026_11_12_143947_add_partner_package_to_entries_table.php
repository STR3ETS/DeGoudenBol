<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            // Naam van het gekozen partnerpakket (editie-instelling); de prijs staat op de orderregel.
            $table->string('partner_package', 120)->nullable()->after('video_addon');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table): void {
            $table->dropColumn('partner_package');
        });
    }
};
