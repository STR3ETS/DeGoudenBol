<?php

namespace App\Domain\Vouchers\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Enums\VoucherStatus;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cadeaubonnenactie van één Top 10-ondernemer (docs/04 §8). Ondernemer draagt de waarde;
 * geen geld via het platform.
 *
 * @property CampaignStatus $status
 */
#[Fillable(['edition_id', 'company_id', 'entry_id', 'province_id', 'terms_version_id', 'starts_at', 'winners_deadline_at', 'last_redeem_day', 'selection_method', 'winner_count', 'voucher_value_cents', 'status', 'opened_notified_at'])]
class VoucherCampaign extends DomainModel
{
    use HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'winners_deadline_at' => 'immutable_datetime',
            'last_redeem_day' => 'immutable_date',
            'winner_count' => 'integer',
            'voucher_value_cents' => 'integer',
            'status' => CampaignStatus::class,
            'opened_notified_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function terms(): BelongsTo
    {
        return $this->belongsTo(TermsVersion::class, 'terms_version_id');
    }

    public function winners(): HasMany
    {
        return $this->hasMany(VoucherWinner::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function shortfalls(): HasMany
    {
        return $this->hasMany(VoucherShortfall::class);
    }

    public function acceptsWinners(): bool
    {
        return $this->status === CampaignStatus::Open && $this->winners_deadline_at->isFuture();
    }

    public function remainingSlots(): int
    {
        return max(0, $this->winner_count - $this->winners()->count());
    }

    /**
     * Rapportage per ondernemer: uitgegeven, geclaimd, verzilverd, verlopen.
     *
     * @return array{winners: int, claimed: int, issued: int, redeemed: int, expired: int, shortfall: int}
     */
    public function report(): array
    {
        $vouchers = $this->vouchers()->get(['status']);

        return [
            'winners' => $this->winners()->count(),
            'claimed' => $this->winners()->whereNotNull('claimed_at')->count(),
            'issued' => $vouchers->count(),
            'redeemed' => $vouchers->where('status', VoucherStatus::Redeemed)->count(),
            'expired' => $vouchers->where('status', VoucherStatus::Expired)->count(),
            'shortfall' => (int) $this->shortfalls()->sum('count'),
        ];
    }
}
