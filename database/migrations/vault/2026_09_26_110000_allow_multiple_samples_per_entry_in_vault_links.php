<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eén inschrijving krijgt meerdere monsters (provinciale ronde, beslisronde, finale);
 * de koppeling blijft uniek per monster.
 */
return new class extends Migration
{
    protected $connection = 'vault';

    public function up(): void
    {
        Schema::connection($this->connection)->table('vault_links', function (Blueprint $table) {
            $table->dropUnique(['entry_lookup']);
            $table->index('entry_lookup');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('vault_links', function (Blueprint $table) {
            $table->dropIndex(['entry_lookup']);
            $table->unique('entry_lookup');
        });
    }
};
