<?php

namespace App\Domain\Platform\Models;

use App\Domain\Participants\Models\Company;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Door een panellid gemeld belangenconflict met een bedrijf. Bron voor de uitsluitingen in
 * het uitserveerschema; de vertaling naar testnummers loopt via de kluis.
 */
#[Fillable(['user_id', 'company_id', 'note'])]
class PanelistConflict extends DomainModel
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
