<?php

namespace App\Support\Cdn;

/**
 * Geen CDN (lokaal en zolang er geen CDN voor de site staat).
 */
final class NullCdnPurger implements CdnPurger
{
    public function purgeAll(): void {}
}
