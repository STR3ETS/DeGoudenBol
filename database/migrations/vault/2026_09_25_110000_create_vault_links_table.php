<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'vault';

    public function up(): void
    {
        Schema::connection($this->connection)->create('vault_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sample_id')->unique();
            $table->unsignedBigInteger('edition_id')->index();
            // Versleuteld met VAULT_ENCRYPTION_KEY (los van APP_KEY).
            $table->text('entry_ref');
            // HMAC van de inschrijving zodat je kunt zoeken zonder te ontsleutelen.
            $table->char('entry_lookup', 64)->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at');
        });

        Schema::connection($this->connection)->create('vault_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('actor', 120);
            $table->string('action', 16)->index();
            $table->unsignedBigInteger('sample_id')->nullable();
            $table->char('entry_lookup', 64)->nullable();
            $table->string('reason');
            $table->string('ip', 45)->nullable();
            $table->dateTime('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('vault_access_logs');
        Schema::connection($this->connection)->dropIfExists('vault_links');
    }
};
