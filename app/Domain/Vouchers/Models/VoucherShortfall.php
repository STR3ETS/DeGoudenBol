<?php

namespace App\Domain\Vouchers\Models;

use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tekort aan ingevoerde winnaars na de deadline: Bonbeheer vult aan en factureert door.
 */
#[Fillable(['voucher_campaign_id', 'count', 'note', 'created_by', 'invoiced_at'])]
class VoucherShortfall extends DomainModel
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'invoiced_at' => 'immutable_datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VoucherCampaign::class, 'voucher_campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
