<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Betaalde zichtbaarheidsplekken per provincie (verzoek Raphael Muskitta 6 nov 2026). Puur marketing: staat
 * los van de test en bestaat bewust niet in het rankingdomein. Elke plek gaat één keer weg, één plek per inschrijving.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visibility_spots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->unsignedInteger('price_cents');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->unique(['edition_id', 'province_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visibility_spots');
    }
};
