<?php

namespace App\Domain\Intake\Models;

use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Enums\PickupStatus;
use App\Support\DutchTime;
use App\Support\Models\DomainModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Een vrijdag waarop de ophaalservice rijdt, met een maximum aantal adressen.
 */
#[Fillable(['edition_id', 'date', 'capacity', 'is_open', 'notes'])]
class PickupDay extends DomainModel
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'capacity' => 10,
        'is_open' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'capacity' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    /**
     * Open vrijdagen die nog te boeken zijn binnen de wijzigingstermijn.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeBookable(Builder $query, int $deadlineHours): Builder
    {
        return $query->where('is_open', true)
            ->where('date', '>=', DutchTime::display(now()->addHours($deadlineHours))->addDay()->toDateString())
            ->orderBy('date');
    }

    public function takenCount(): int
    {
        return $this->requests()->whereIn('status', PickupStatus::occupying())->count();
    }

    public function availableCount(): int
    {
        return max(0, $this->capacity - $this->takenCount());
    }

    /**
     * Uiterste moment om deze vrijdag te kiezen of te wijzigen.
     */
    public function changeDeadline(int $hours): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date->toDateString().' 00:00', DutchTime::zone())->subHours($hours)->utc();
    }

    public function label(): string
    {
        return ucfirst(DutchTime::format($this->date, 'dddd D MMMM'));
    }
}
