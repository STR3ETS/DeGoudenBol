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
}
