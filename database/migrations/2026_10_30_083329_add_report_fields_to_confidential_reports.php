<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persoonlijk rapport (briefing 29 okt §7): voor iedere deelnemer, binnen drie dagen na de keuring,
 * met algemene indruk en een uiterste datum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confidential_reports', function (Blueprint $table) {
            $table->text('general_impression')->nullable()->after('opportunities');
            $table->date('tested_on')->nullable()->after('general_impression');
            $table->dateTime('due_at')->nullable()->index()->after('tested_on');
        });
    }

    public function down(): void
    {
        Schema::table('confidential_reports', function (Blueprint $table) {
            $table->dropColumn(['general_impression', 'tested_on', 'due_at']);
        });
    }
};
