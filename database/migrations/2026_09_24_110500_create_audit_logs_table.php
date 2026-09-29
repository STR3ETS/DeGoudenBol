<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('actor');
            $table->string('action', 64)->index();
            $table->nullableMorphs('subject');
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64)->unique();
            $table->dateTime('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
