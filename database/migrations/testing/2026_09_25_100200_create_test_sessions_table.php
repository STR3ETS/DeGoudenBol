<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'testing';

    public function up(): void
    {
        Schema::connection($this->connection)->create('test_sessions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->unsignedBigInteger('edition_id')->index();
            $table->unsignedBigInteger('test_location_id')->nullable();
            $table->string('round', 16);
            $table->string('name', 80)->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('status', 16)->index();
            $table->unsignedSmallInteger('max_samples');
            $table->dateTime('schedule_generated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('session_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sample_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('serving_order')->default(0);
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('session_panelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panelist_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['test_session_id', 'panelist_id']);
        });

        Schema::connection($this->connection)->create('serving_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panelist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('serving_order');
            $table->string('status', 16)->index();
            $table->timestamps();

            $table->unique(['test_session_id', 'panelist_id', 'sample_id']);
        });

        Schema::connection($this->connection)->create('serving_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panelist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sample_id')->constrained()->cascadeOnDelete();
            // Alleen de reden-code; nooit een naam of bedrijf.
            $table->string('reason_code', 16);
            $table->dateTime('created_at');

            $table->unique(['test_session_id', 'panelist_id', 'sample_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('serving_exclusions');
        Schema::connection($this->connection)->dropIfExists('serving_assignments');
        Schema::connection($this->connection)->dropIfExists('session_panelists');
        Schema::connection($this->connection)->dropIfExists('session_samples');
        Schema::connection($this->connection)->dropIfExists('test_sessions');
    }
};
