<?php

namespace App\Domain\Participants\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\TestLocation;
use App\Support\DutchTime;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tijdslot waarop deelnemers hun monster aanleveren (besluit 5: aanleveren op tijdslot).
 */
#[Fillable(['edition_id', 'test_location_id', 'starts_at', 'ends_at', 'capacity', 'notes'])]
class DeliverySlot extends DomainModel
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'capacity' => 'integer',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function testLocation(): BelongsTo
    {
        return $this->belongsTo(TestLocation::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())->orderBy('starts_at');
    }

    public function takenCount(): int
    {
        return $this->entries()->occupyingPlace()->count();
    }

    public function availableCount(): int
    {
        return max(0, $this->capacity - $this->takenCount());
    }

    public function isFull(): bool
    {
        return $this->availableCount() === 0;
    }

    public function label(): string
    {
        return DutchTime::format($this->starts_at, 'dddd D MMMM, HH:mm').' – '.DutchTime::format($this->ends_at, 'HH:mm');
    }
}
