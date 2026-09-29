<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'testing';

    public function up(): void
    {
        Schema::connection($this->connection)->create('samples', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->unsignedBigInteger('edition_id')->index();
            $table->string('round', 16)->index();
            $table->string('sample_number', 8)->nullable();
            $table->string('status', 24)->index();
            $table->unsignedBigInteger('test_location_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'round', 'sample_number']);
        });

        Schema::connection($this->connection)->create('intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('received_at');
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->unsignedSmallInteger('piece_count');
            $table->string('photo_path')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->dateTime('freshness_expires_at')->index();
            $table->dateTime('label_printed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('intakes');
        Schema::connection($this->connection)->dropIfExists('samples');
    }
};
