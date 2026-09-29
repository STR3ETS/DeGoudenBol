<?php

namespace App\Support\Cdn;

/**
 * CDN-invalidatie na een wijziging aan de publiekssite.
 */
interface CdnPurger
{
    public function purgeAll(): void;
}
