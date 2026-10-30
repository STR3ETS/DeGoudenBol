<?php

namespace App\Domain\Intake\Models;

use App\Domain\Commerce\Models\Order;
use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aanvraag voor de ophaalservice: een teamlid haalt de oliebollen op vrijdag op. Pas actief na
 * betaling van de voorrijkosten.
 *
 * @property PickupStatus $status
 */
#[Fillable([
    'edition_id',
    'entry_id',
    'pickup_day_id',
    'participant_user_id',
    'order_id',
    'company_name',
    'contact_name',
    'phone',
    'street',
    'house_number',
    'postcode',
    'city',
    'instructions',
    'status',
    'driver_name',
    'route_order',
    'paid_at',
    'scheduled_at',
    'picked_up_at',
    'received_at',
    'cancelled_at',
])]
class PickupRequest extends DomainModel
{
    use HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PickupStatus::class,
            'route_order' => 'integer',
            'paid_at' => 'immutable_datetime',
            'scheduled_at' => 'immutable_datetime',
            'picked_up_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
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

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(PickupDay::class, 'pickup_day_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(ParticipantUser::class, 'participant_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function address(): string
    {
        return trim("{$this->street} {$this->house_number}").", {$this->postcode} {$this->city}";
    }

    /**
     * Link naar het adres in Google Maps (kaartweergave voor de chauffeur).
     */
    public function mapsUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->address());
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
