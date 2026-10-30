<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Briefing 29 okt: kortingsactie (filmpje delen, administrator keurt) en de keuze tussen zelf brengen
 * en laten ophalen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('rate', 16)->default('standard')->after('package_id');
            $table->string('discount_status', 16)->nullable()->index()->after('rate');
            $table->string('discount_proof_url', 500)->nullable()->after('discount_status');
            $table->string('discount_proof_path')->nullable()->after('discount_proof_url');
            $table->dateTime('discount_submitted_at')->nullable()->after('discount_proof_path');
            $table->dateTime('discount_reviewed_at')->nullable()->after('discount_submitted_at');
            $table->foreignId('discount_reviewed_by')->nullable()->after('discount_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('discount_note')->nullable()->after('discount_reviewed_by');
            $table->string('delivery_method', 16)->nullable()->after('delivery_slot_id');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_reviewed_by');
            $table->dropColumn(['rate', 'discount_status', 'discount_proof_url', 'discount_proof_path', 'discount_submitted_at', 'discount_reviewed_at', 'discount_note', 'delivery_method']);
        });
    }
};
