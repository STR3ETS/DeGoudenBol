<?php

namespace App\Domain\Testing\Contracts;

use App\Domain\Testing\Data\Exclusion;
use App\Domain\Testing\Models\TestSession;

/**
 * Levert de uitsluitingen (belangenconflict, allergeen) voor een sessie. De implementatie
 * leeft buiten de testketen (via de kluis); de testketen ziet alleen panellid, monster en reden.
 */
interface ExclusionSource
{
    /**
     * @return list<Exclusion>
     */
    public function exclusionsFor(TestSession $session): array;
}
