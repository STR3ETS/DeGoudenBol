<?php

use App\Domain\Participants\Enums\Allergen;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nieuwe allergenenlijst (Bennie, 30 okt 2026): "pinda" gaat op in "pinda's / noten"; selderij,
 * schaaldieren, vis en weekdieren vervallen. Geldt voor inschrijvingen en voor panelleden, zodat de
 * uitsluiting bij het uitserveren blijft kloppen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->normalize(DB::connection(), 'entries');

        if (Schema::connection('testing')->hasTable('panelists')) {
            $this->normalize(DB::connection('testing'), 'panelists');
        }
    }

    public function down(): void
    {
        // Gegevensmigratie; de oude codes zijn niet terug te halen.
    }

    private function normalize($connection, string $table): void
    {
        $connection->table($table)->whereNotNull('allergens')->orderBy('id')->each(function (object $row) use ($connection, $table): void {
            $codes = json_decode((string) $row->allergens, true);

            if (! is_array($codes)) {
                return;
            }

            $normalized = Allergen::normalize(array_map('strval', $codes));

            if ($normalized !== array_values($codes)) {
                $connection->table($table)->where('id', $row->id)->update(['allergens' => json_encode($normalized)]);
            }
        });
    }
};
