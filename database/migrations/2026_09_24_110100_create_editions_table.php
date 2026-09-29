<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('name');
            $table->string('slug', 32)->unique();
            $table->string('status', 32)->index();
            $table->json('settings');
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->date('first_test_day')->nullable();
            $table->date('last_test_day')->nullable();
            $table->dateTime('freeze_at')->nullable();
            $table->dateTime('main_publication_at')->nullable();
            $table->date('final_test_day')->nullable();
            $table->dateTime('national_result_at')->nullable();
            $table->date('last_redeem_day')->nullable();
            $table->timestamps();
        });

        Schema::create('edition_province', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('capacity');
            $table->dateTime('reveal_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'province_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edition_province');
        Schema::dropIfExists('editions');
    }
};
