<?php

namespace App\Support\Models;

use App\Support\Models\Concerns\NormalizesDatesToUtc;
use Illuminate\Database\Eloquent\Model;

/**
 * Basis voor alle domeinmodellen. Tijden worden in UTC opgeslagen (app.timezone)
 * en pas bij weergave naar Nederlandse tijd omgezet (app.display_timezone).
 */
abstract class DomainModel extends Model
{
    use NormalizesDatesToUtc;
}
