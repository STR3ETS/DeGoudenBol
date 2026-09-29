<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('product_type', 64)->default('oliebol');
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['edition_id', 'product_type', 'version']);
        });

        Schema::create('scoring_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scoring_model_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('max_points');
            $table->unsignedTinyInteger('sort')->default(0);
            $table->unsignedTinyInteger('tie_break_rank')->nullable();
            $table->timestamps();

            $table->unique(['scoring_model_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_criteria');
        Schema::dropIfExists('scoring_models');
    }
};
