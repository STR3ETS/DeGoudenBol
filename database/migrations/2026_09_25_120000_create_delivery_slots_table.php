<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_location_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('capacity');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('entries', function (Blueprint $table) {
            $table->foreignId('delivery_slot_id')->nullable()->after('terms_acceptance_id')->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable()->after('confirmed_at');
            $table->string('delivery_code', 12)->nullable()->unique()->after('product_notes');
        });

        Schema::create('panelist_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panelist_conflicts');

        Schema::table('entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_slot_id');
            $table->dropColumn(['scheduled_at', 'delivery_code']);
        });

        Schema::dropIfExists('delivery_slots');
    }
};
