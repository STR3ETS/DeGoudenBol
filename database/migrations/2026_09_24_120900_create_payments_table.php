<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16);
            $table->string('provider_id', 64)->nullable()->unique();
            $table->string('status', 16)->index();
            $table->unsignedInteger('amount_cents');
            $table->string('method', 32)->nullable();
            $table->text('checkout_url')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
