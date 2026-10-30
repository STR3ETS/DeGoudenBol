<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sponsoracties (briefing 29 okt §10): vouchers, proefpakketten, cadeaus, adviesgesprekken of prijzen
 * voor winnaars. Eerst redactionele goedkeuring, dan zichtbaar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsor_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('audience', 24);
            $table->string('title', 160);
            $table->text('description');
            $table->string('status', 16)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_offers');
    }
};
