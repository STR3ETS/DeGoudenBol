<?php

namespace App\Domain\Marketing\Models;

use App\Domain\Edition\Models\Province;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mediacontact: regionaal (provincie) of landelijk (geen provincie). Ontvangt de embargo-perskit.
 */
#[Fillable(['name', 'outlet', 'email', 'province_id', 'notes'])]
class MediaContact extends DomainModel
{
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function isNational(): bool
    {
        return $this->province_id === null;
    }
}
