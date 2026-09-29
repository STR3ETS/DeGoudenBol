<?php

namespace App\Domain\Edition\Models;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * De twaalf provincies. Vast; de capaciteit per editie staat op de koppeltabel.
 */
#[Fillable(['name', 'slug', 'code', 'sort'])]
class Province extends DomainModel
{
    use HasFactory;

    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class)
            ->using(EditionProvince::class)
            ->withPivot(['capacity', 'reveal_at'])
            ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
