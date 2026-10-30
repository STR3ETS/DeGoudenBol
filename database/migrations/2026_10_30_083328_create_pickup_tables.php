<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ophaalservice (briefing 29 okt §5): alleen op vrijdag, vaste voorrijkosten, capaciteit per vrijdag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('capacity')->default(10);
            $table->boolean('is_open')->default(true);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'date']);
        });

        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_day_id')->constrained()->restrictOnDelete();
            $table->foreignId('participant_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name', 160);
            $table->string('contact_name', 120);
            $table->string('phone', 40);
            $table->string('street', 120);
            $table->string('house_number', 16);
            $table->string('postcode', 10)->index();
            $table->string('city', 120);
            $table->text('instructions')->nullable();
            $table->string('status', 16)->index();
            $table->string('driver_name', 120)->nullable();
            $table->unsignedSmallInteger('route_order')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
        Schema::dropIfExists('pickup_days');
    }
};
