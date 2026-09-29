<?php

namespace App\Domain\Platform\Exceptions;

use App\Domain\Platform\Enums\StaffRole;
use DomainException;

class ForbiddenRoleCombinationException extends DomainException
{
    public static function for(StaffRole $first, StaffRole $second): self
    {
        return new self(sprintf(
            'De rollen "%s" en "%s" mogen niet samenvallen bij één persoon (blind protocol).',
            $first->getLabel(),
            $second->getLabel(),
        ));
    }
}
