<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('terms_acceptance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->index();
            $table->string('public_name');
            $table->string('tagline')->nullable();
            $table->json('allergens')->nullable();
            $table->text('product_notes')->nullable();
            $table->dateTime('reservation_expires_at')->nullable()->index();
            $table->dateTime('registered_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'company_id']);
            $table->index(['edition_id', 'province_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
