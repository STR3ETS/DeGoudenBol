<?php

namespace App\Domain\Ranking\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Vaste publicatiebatch (di/vr 12:00): nieuwe binnenkomers zijn niet herleidbaar tot één sessie.
 *
 * @property BatchStatus $status
 */
#[Fillable(['edition_id', 'scheduled_at', 'status', 'submitted_by', 'submitted_at', 'published_at'])]
class PublicationBatch extends DomainModel
{
    public const int REQUIRED_APPROVALS = 2;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BatchStatus::class,
            'scheduled_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PublicationItem::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isPublished(): bool
    {
        return $this->status === BatchStatus::Published;
    }

    public function isDue(): bool
    {
        return $this->status === BatchStatus::Approved && $this->scheduled_at->isPast();
    }

    public function label(): string
    {
        return 'Batch '.$this->scheduled_at->timezone(config('app.display_timezone'))->format('d-m-Y H:i');
    }
}
