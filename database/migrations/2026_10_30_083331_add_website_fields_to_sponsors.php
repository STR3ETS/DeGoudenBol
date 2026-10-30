<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sponsoraanmelding via de website (briefing 29 okt §8): vaste pakketten direct afrekenen, maatwerk
 * en de Top 10-reservering als aanvraag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('source', 16)->default('backoffice')->after('status');
            $table->foreignId('requested_product_id')->nullable()->after('source')->constrained('products')->nullOnDelete();
            $table->foreignId('requested_province_id')->nullable()->after('requested_product_id')->constrained('provinces')->nullOnDelete();
            $table->text('request_note')->nullable()->after('requested_province_id');
            $table->dateTime('requested_at')->nullable()->after('request_note');
            $table->dateTime('approved_at')->nullable()->after('requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_product_id');
            $table->dropConstrainedForeignId('requested_province_id');
            $table->dropColumn(['source', 'request_note', 'requested_at', 'approved_at']);
        });
    }
};
