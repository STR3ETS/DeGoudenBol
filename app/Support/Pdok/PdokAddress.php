<?php

namespace App\Support\Pdok;

final readonly class PdokAddress
{
    public function __construct(
        public string $street,
        public string $city,
        public ?string $provinceName,
        public ?float $lat,
        public ?float $lng,
        public string $postcode,
        public string $houseNumber,
    ) {}
}
