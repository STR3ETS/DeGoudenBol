<?php

namespace App\Domain\Charities\Models;

use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 10%-reservering over één factuurregel met `counts_for_charity`. Naar het doel van de betaler,
 * anders naar de regionale pot van diens provincie (of de landelijke pot).
 */
#[Fillable(['edition_id', 'order_line_id', 'charity_id', 'regional_pot_province_id', 'payer_type', 'payer_id', 'basis_cents', 'amount_cents', 'basis'])]
class CharityReservation extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basis_cents' => 'integer',
            'amount_cents' => 'integer',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }

    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    public function regionalPotProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'regional_pot_province_id');
    }

    public function payer(): MorphTo
    {
        return $this->morphTo();
    }

    public function isInPot(): bool
    {
        return $this->charity_id === null;
    }
}
