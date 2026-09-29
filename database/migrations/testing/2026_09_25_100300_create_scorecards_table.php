<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'testing';

    public function up(): void
    {
        Schema::connection($this->connection)->create('scorecards', function (Blueprint $table) {
            $table->id();
            // Door het apparaat gegenereerd: maakt de synchronisatie idempotent.
            $table->uuid('uuid')->unique();
            $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panelist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->json('scores');
            $table->text('strengths')->nullable();
            $table->text('opportunities')->nullable();
            $table->dateTime('submitted_at');
            $table->char('hash', 64);
            $table->string('source', 8);
            $table->boolean('is_valid')->default(true);
            $table->string('invalidated_reason')->nullable();
            $table->unsignedBigInteger('invalidated_by')->nullable();
            $table->dateTime('invalidated_at')->nullable();
            $table->dateTime('created_at');

            $table->unique(['sample_id', 'panelist_id']);
        });

        Schema::connection($this->connection)->create('paper_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panelist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->json('scores');
            $table->text('strengths')->nullable();
            $table->text('opportunities')->nullable();
            $table->unsignedBigInteger('entered_by');
            $table->dateTime('created_at');

            $table->unique(['sample_id', 'panelist_id', 'entered_by']);
        });

        Schema::connection($this->connection)->create('score_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scorecard_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->json('before');
            $table->json('after');
            $table->string('status', 16)->index();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('scoring_model_version');
            $table->unsignedSmallInteger('card_count');
            $table->json('criterion_averages');
            $table->decimal('total_raw', 6, 2);
            $table->decimal('total', 4, 1);
            $table->json('flags');
            $table->string('status', 16)->index();
            $table->dateTime('computed_at');
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('results');
        Schema::connection($this->connection)->dropIfExists('score_corrections');
        Schema::connection($this->connection)->dropIfExists('paper_entries');
        Schema::connection($this->connection)->dropIfExists('scorecards');
    }
};
