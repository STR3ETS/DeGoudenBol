<?php

namespace App\Domain\Participants\Actions;

use App\Domain\Commerce\Actions\CreateOrderForEntry;
use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Data\RegistrationData;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Enums\ModerationStatus;
use App\Domain\Participants\Events\DiscountRequested;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Models\TermsAcceptance;
use App\Domain\Participants\Services\ProvinceCapacity;
use App\Domain\Platform\Services\AuditLogger;
use Illuminate\Support\Str;

/**
 * Stap 1 uit het kernproces: maakt account, bedrijf, standplaats, akkoord, inschrijving
 * (met gereserveerde plek) en order in één transactie. Betaling volgt daarna.
 */
final class RegisterEntry
{
    public function __construct(
        private readonly ProvinceCapacity $capacity,
        private readonly CreateOrderForEntry $createOrder,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Edition $edition, RegistrationData $data): Entry
    {
        $province = Province::query()->findOrFail($data->provinceId);
        $package = Package::query()->where('edition_id', $edition->getKey())->active()->findOrFail($data->packageId);
        // Zolang de openingsactie loopt, vervangt die de standaardprijs en de kortingsactie.
        $openingOffer = $edition->settings->openingOfferEnabled();
        $wantsDiscount = ! $openingOffer && $data->rate === EntryRate::Discount && $edition->settings->discountEnabled();
        $rate = match (true) {
            $openingOffer => EntryRate::OpeningOffer,
            $wantsDiscount => EntryRate::Discount,
            default => EntryRate::Standard,
        };
        $plainAddon = $data->plainAddon && $edition->settings->plainAddonEnabled();

        // Met kortingsaanvraag blijft de plek langer vast: eerst bewijs en controle, dan pas betalen.
        $reservationMinutes = $wantsDiscount
            ? $edition->settings->discountHoldDays * 24 * 60
            : (int) config('commerce.reservation_minutes', 60);

        return $this->capacity->reserve($edition, $province, function () use ($edition, $province, $package, $data, $reservationMinutes, $wantsDiscount, $rate, $plainAddon): Entry {
            $user = ParticipantUser::query()->firstOrCreate(
                ['email' => Str::lower($data->email)],
                ['name' => $data->contactName, 'phone' => $data->phone],
            );

            $company = Company::query()->create([
                'name' => $data->companyName,
                'type' => $data->companyType,
                'kvk_number' => $data->kvkNumber,
                'website' => $data->website,
                'contact_name' => $data->contactName,
                'contact_phone' => $data->phone,
            ]);

            $company->users()->attach($user->getKey(), ['role' => CompanyUserRole::Owner]);

            $company->locations()->create([
                'street' => $data->street,
                'house_number' => $data->houseNumber,
                'postcode' => $data->postcode,
                'city' => $data->city,
                'province_id' => $province->getKey(),
                'lat' => $data->lat,
                'lng' => $data->lng,
                'is_primary' => true,
            ]);

            $company->profile()->create([
                'tagline' => $data->tagline,
                'specialties' => [],
            ])->forceFill(['moderation_status' => ModerationStatus::Draft])->save();

            $acceptance = TermsAcceptance::query()->create([
                'participant_user_id' => $user->getKey(),
                'company_id' => $company->getKey(),
                'terms_version_id' => $data->termsVersionId,
                'accepted_at' => now(),
                'ip' => $data->ip,
                'user_agent' => $data->userAgent ? Str::limit($data->userAgent, 250, '') : null,
            ]);

            $expiresAt = now()->addMinutes($reservationMinutes);

            $entry = Entry::query()->create([
                'edition_id' => $edition->getKey(),
                'company_id' => $company->getKey(),
                'province_id' => $province->getKey(),
                'package_id' => $package->getKey(),
                'rate' => $rate,
                'plain_addon' => $plainAddon,
                'discount_status' => $wantsDiscount ? (filled($data->discountProofUrl) ? DiscountStatus::Submitted : DiscountStatus::AwaitingProof) : null,
                'discount_proof_url' => $wantsDiscount ? $data->discountProofUrl : null,
                'discount_submitted_at' => $wantsDiscount && filled($data->discountProofUrl) ? now() : null,
                'terms_acceptance_id' => $acceptance->getKey(),
                'status' => EntryStatus::PendingPayment,
                'public_name' => $data->publicName,
                'tagline' => $data->tagline,
                'allergens' => $data->allergens,
                'product_notes' => $data->productNotes,
                'reservation_expires_at' => $expiresAt,
                'registered_at' => now(),
            ]);

            ($this->createOrder)($entry, $package, $user, $expiresAt);

            $this->audit->record('entry.registered', $entry, [
                'edition' => $edition->year,
                'province' => $province->slug,
                'package' => $package->code,
                'rate' => $rate->value,
                'plain_addon' => $plainAddon,
            ], null);

            if ($wantsDiscount) {
                DiscountRequested::dispatch($entry);
            }

            return $entry->load(['company', 'province', 'package', 'order']);
        });
    }
}
