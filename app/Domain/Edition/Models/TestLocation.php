<?php

namespace App\Domain\Edition\Models;

use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Fysieke testlocatie. Eigen entiteit zodat meerdere locaties later geen verbouwing vragen.
 */
#[Fillable(['name', 'street', 'postcode', 'city', 'notes', 'capacity', 'is_active'])]
class TestLocation extends DomainModel
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
