<?php

namespace App\Domain\Participants\Data;

use App\Domain\Participants\Enums\CompanyType;
use App\Domain\Participants\Enums\EntryRate;

/**
 * Alles wat de aanmeldflow verzamelt, in één onveranderlijk object.
 */
final readonly class RegistrationData
{
    /**
     * @param  list<string>  $allergens
     */
    public function __construct(
        public string $companyName,
        public CompanyType $companyType,
        public ?string $kvkNumber,
        public string $contactName,
        public string $email,
        public ?string $phone,
        public ?string $website,
        public string $street,
        public string $houseNumber,
        public string $postcode,
        public string $city,
        public int $provinceId,
        public ?float $lat,
        public ?float $lng,
        public string $publicName,
        public ?string $tagline,
        public array $allergens,
        public ?string $productNotes,
        public int $packageId,
        public int $termsVersionId,
        public ?string $ip = null,
        public ?string $userAgent = null,
        public EntryRate $rate = EntryRate::Standard,
        public ?string $discountProofUrl = null,
        public bool $plainAddon = false,
        public bool $pickup = false,
        public bool $homeAddress = false,
        /** @var array{pickup_day_id: int|null, street: string, house_number: string, postcode: string, city: string, phone: string|null, instructions: string|null}|null */
        public ?array $pickupPreference = null,
        public ?int $visibilityPosition = null,
        public bool $videoAddon = false,
    ) {}
}
