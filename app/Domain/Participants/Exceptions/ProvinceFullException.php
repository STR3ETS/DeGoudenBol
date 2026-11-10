<?php

namespace App\Domain\Participants\Exceptions;

use App\Domain\Edition\Models\Province;
use DomainException;

class ProvinceFullException extends DomainException
{
    public static function for(Province $province): self
    {
        return new self("Alle plekken in {$province->name} zijn bezet.");
    }

    /**
     * Het landelijke maximum van de editie is bereikt; dan is iedere provincie vol.
     */
    public static function editionFull(): self
    {
        return new self('Alle plekken voor deze editie zijn bezet. De inschrijving is vol.');
    }
}
