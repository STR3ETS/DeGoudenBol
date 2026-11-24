<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('press_releases', function (Blueprint $table): void {
            // Onderdelen uit het persberichtsjabloon die niet in de platte tekst zitten; allemaal optioneel.
            $table->string('subtitle', 250)->nullable()->after('title');
            $table->text('quote')->nullable()->after('body');
            $table->string('quote_name', 120)->nullable()->after('quote');
            $table->string('quote_role', 120)->nullable()->after('quote_name');
        });
    }

    public function down(): void
    {
        Schema::table('press_releases', function (Blueprint $table): void {
            $table->dropColumn(['subtitle', 'quote', 'quote_name', 'quote_role']);
        });
    }
};
