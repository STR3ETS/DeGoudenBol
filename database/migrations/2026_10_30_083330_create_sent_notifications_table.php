<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logboek van statusmails, zodat een herhaald webhookevent of een dubbele statuswijziging nooit een
 * tweede mail oplevert (briefing 29 okt §11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sent_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->unique();
            $table->string('notification', 191);
            $table->string('recipient', 191);
            $table->dateTime('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_notifications');
    }
};
