<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->decimal('vat_rate', 5, 2)->default(21.00);
            $table->unsignedSmallInteger('stock_per_province')->nullable();
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort')->default(0);
            $table->json('entitlements')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
