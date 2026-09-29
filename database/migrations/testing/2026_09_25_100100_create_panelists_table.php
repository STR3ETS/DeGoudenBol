<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'testing';

    public function up(): void
    {
        Schema::connection($this->connection)->create('panelists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('edition_id')->nullable()->index();
            $table->string('display_code', 8)->unique();
            $table->string('pool_status', 16)->index();
            $table->unsignedInteger('fee_per_session_cents')->default(0);
            // Gezondheidsgegevens: alleen allergeencategorieën, met toestemming, wissen na de editie.
            $table->json('allergens')->nullable();
            $table->dateTime('consent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('panelists');
    }
};
