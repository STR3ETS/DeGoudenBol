<?php

namespace App\Domain\Participants\Models;

use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Intake\Models\PickupRequest;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Participants\Enums\DeliveryMethod;
use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\PublicationItem;
use App\Models\User;
use App\Support\Models\DomainModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

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
    'rate',
    'discount_status',
    'discount_proof_url',
    'discount_proof_path',
    'discount_submitted_at',
    'discount_reviewed_at',
    'discount_reviewed_by',
    'discount_note',
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
    'delivery_method',
    'scheduled_at',
    'delivery_code',
    'linked_at',
    'published_at',
])]
class Entry extends DomainModel
{
    use HasFactory, HasUlids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'rate' => 'standard',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EntryStatus::class,
            'rate' => EntryRate::class,
            'discount_status' => DiscountStatus::class,
            'discount_submitted_at' => 'immutable_datetime',
            'discount_reviewed_at' => 'immutable_datetime',
            'delivery_method' => DeliveryMethod::class,
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

    public function pickupRequest(): HasOne
    {
        return $this->hasOne(PickupRequest::class);
    }

    public function discountReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discount_reviewed_by');
    }

    /**
     * Product en variant zoals op het rapport, uit de editie-instelling (bijvoorbeeld krenten_rozijnen).
     */
    public function productLabel(): string
    {
        $variant = $this->edition?->settings->productVariant;

        return match ($variant) {
            null, '' => 'Oliebol',
            'krenten_rozijnen' => 'Oliebol met krenten en rozijnen',
            'naturel' => 'Oliebol naturel',
            default => 'Oliebol ('.str_replace('_', ' ', $variant).')',
        };
    }

    public function wantsDiscount(): bool
    {
        return $this->rate === EntryRate::Discount;
    }

    /**
     * Mag de deelnemer nu afrekenen? Niet zolang een kortingsaanvraag nog beslist moet worden.
     */
    public function canPay(): bool
    {
        if (! $this->isPendingPayment() || $this->isReservationExpired()) {
            return false;
        }

        return ! ($this->wantsDiscount() && $this->discount_status?->blocksPayment());
    }

    /**
     * Kan de deelnemer (opnieuw) bewijs voor de kortingsactie aanleveren?
     */
    public function canSubmitDiscountProof(): bool
    {
        return $this->isPendingPayment()
            && ! $this->isReservationExpired()
            && $this->wantsDiscount()
            && in_array($this->discount_status, [DiscountStatus::AwaitingProof, DiscountStatus::Submitted, DiscountStatus::Rejected], true);
    }

    public function discountProofUrl(): ?string
    {
        return $this->discount_proof_path ? Storage::disk('local')->temporaryUrl($this->discount_proof_path, now()->addMinutes(30)) : null;
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
