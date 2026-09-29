<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Models\Company;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Bakt met [sponsor]": koppeling sponsor–deelnemer die de deelnemer zelf bevestigt.
 */
#[Fillable(['edition_id', 'sponsor_id', 'company_id', 'placement_id', 'requested_at', 'confirmed_by_company_at', 'declined_at'])]
class SponsorLink extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_at' => 'immutable_datetime',
            'confirmed_by_company_at' => 'immutable_datetime',
            'declined_at' => 'immutable_datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function isPending(): bool
    {
        return $this->confirmed_by_company_at === null && $this->declined_at === null;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_by_company_at !== null && $this->declined_at === null;
    }
}
