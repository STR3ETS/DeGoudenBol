<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publication_items', function (Blueprint $table) {
            $table->string('round', 16)->default('provincial')->after('province_id')->index();
        });

        Schema::table('edition_province', function (Blueprint $table) {
            $table->dateTime('revealed_at')->nullable()->after('reveal_at');
        });

        Schema::create('tie_break_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->restrictOnDelete();
            $table->string('scope', 24);
            $table->unsignedSmallInteger('position');
            $table->char('key', 64);
            $table->json('entry_ids');
            $table->json('scores')->nullable();
            $table->json('outcome_order')->nullable();
            $table->string('status', 16)->index();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'province_id', 'key']);
        });

        Schema::create('finalists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->restrictOnDelete();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->string('origin', 24);
            $table->unsignedSmallInteger('province_position');
            $table->string('status', 16)->index();
            $table->dateTime('invited_at');
            $table->dateTime('responded_at')->nullable();
            $table->foreignId('replaced_by_id')->nullable()->constrained('finalists')->nullOnDelete();
            $table->timestamps();

            $table->unique(['edition_id', 'entry_id']);
        });

        Schema::create('correction_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->json('original_snapshot');
            $table->json('new_snapshot')->nullable();
            $table->string('status', 24)->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('applied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_cases');
        Schema::dropIfExists('finalists');
        Schema::dropIfExists('tie_break_rounds');

        Schema::table('edition_province', function (Blueprint $table) {
            $table->dropColumn('revealed_at');
        });

        Schema::table('publication_items', function (Blueprint $table) {
            $table->dropColumn('round');
        });
    }
};
