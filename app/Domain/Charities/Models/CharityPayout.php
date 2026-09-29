<?php

namespace App\Domain\Charities\Models;

use App\Domain\Edition\Models\Edition;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uitbetaling aan een goed doel; datum en bedrag staan openbaar op de goede-doelenpagina.
 */
#[Fillable(['charity_id', 'edition_id', 'amount_cents', 'paid_at', 'reference', 'note', 'created_by'])]
class CharityPayout extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_at' => 'immutable_date',
        ];
    }

    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
