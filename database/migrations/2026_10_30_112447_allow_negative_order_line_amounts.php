<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Een kortingsregel (kortingsactie, briefing 29 okt) heeft een negatief bedrag. De bedragkolommen van
 * orderregels waren unsigned; daardoor faalde het goedkeuren van een korting op MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->integer('unit_price_cents')->change();
            $table->integer('subtotal_cents')->change();
            $table->integer('vat_cents')->change();
            $table->integer('total_cents')->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->unsignedInteger('unit_price_cents')->change();
            $table->unsignedInteger('subtotal_cents')->change();
            $table->unsignedInteger('vat_cents')->change();
            $table->unsignedInteger('total_cents')->change();
        });
    }
};
