<?php

namespace App\Domain\Participants\Models;

use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\PublicationItem;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Inschrijving van een bedrijf voor één editie. Eén per bedrijf per editie.
 * Het testnummer staat hier nooit; die koppeling leeft alleen in de kluis.
 *
 * @property EntryStatus $status
 */
#[Fillable([
    'edition_id',
    'company_id',
    'province_id',
    'package_id',
    'terms_acceptance_id',
    'status',
    'public_name',
    'tagline',
    'allergens',
    'product_notes',
    'reservation_expires_at',
    'registered_at',
    'confirmed_at',
    'withdrawn_at',
    'delivery_slot_id',
    'scheduled_at',
    'delivery_code',
    'linked_at',
    'published_at',
])]
class Entry extends DomainModel
{
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EntryStatus::class,
            'allergens' => 'array',
            'reservation_expires_at' => 'immutable_datetime',
            'registered_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'scheduled_at' => 'immutable_datetime',
            'linked_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'withdrawn_at' => 'immutable_datetime',
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

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function deliverySlot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class);
    }

    public function termsAcceptance(): BelongsTo
    {
        return $this->belongsTo(TermsAcceptance::class);
    }

    public function order(): MorphOne
    {
        return $this->morphOne(Order::class, 'orderable')->latestOfMany();
    }

    public function publicationItems(): HasMany
    {
        return $this->hasMany(PublicationItem::class);
    }

    public function recognitions(): HasMany
    {
        return $this->hasMany(Recognition::class);
    }

    public function objections(): HasMany
    {
        return $this->hasMany(Objection::class);
    }

    public function confidentialReport(): HasOne
    {
        return $this->hasOne(ConfidentialReport::class);
    }

    public function finalist(): HasOne
    {
        return $this->hasOne(Finalist::class)->latestOfMany();
    }

    /**
     * Laatst gepubliceerde uitslag (openbaar of vertrouwelijk). Verwacht `publicationItems.batch` geladen.
     */
    public function publishedItem(): ?PublicationItem
    {
        return $this->publicationItems
            ->filter(fn (PublicationItem $item) => $item->batch?->isPublished())
            ->sortByDesc(fn (PublicationItem $item) => [$item->batch->published_at?->timestamp ?? 0, $item->getKey()])
            ->first();
    }

    /**
     * Openbaar cijfer, of null zolang er niets openbaar is.
     */
    public function publicTotal(): ?float
    {
        $item = $this->publishedItem();

        return $item?->isPublic() && $this->status->isPubliclyVisible() ? $item->total() : null;
    }

    /**
     * Inschrijvingen die een plek bezetten: alles behalve geannuleerd/teruggetrokken,
     * en een onbetaalde reservering alleen zolang die niet is verlopen.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOccupyingPlace(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereIn('status', array_map(
                fn (EntryStatus $status) => $status->value,
                array_filter(EntryStatus::occupying(), fn (EntryStatus $status) => $status !== EntryStatus::PendingPayment),
            ))->orWhere(function (Builder $query): void {
                $query->where('status', EntryStatus::PendingPayment)
                    ->where('reservation_expires_at', '>', now());
            });
        });
    }

    /**
     * Bevestigde deelnemers: betaald en niet geannuleerd of teruggetrokken.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (EntryStatus $status) => $status->value,
            array_filter(EntryStatus::occupying(), fn (EntryStatus $status) => $status !== EntryStatus::PendingPayment),
        ));
    }

    public function isPendingPayment(): bool
    {
        return $this->status === EntryStatus::PendingPayment;
    }

    public function isReservationExpired(): bool
    {
        return $this->isPendingPayment()
            && $this->reservation_expires_at !== null
            && $this->reservation_expires_at->isPast();
    }
}
