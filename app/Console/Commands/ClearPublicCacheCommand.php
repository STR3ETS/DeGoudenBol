<?php

namespace App\Console\Commands;

use App\Support\PublicCache;
use Illuminate\Console\Command;

/**
 * Na een deploy (nieuwe assets) of een handmatige inhoudswijziging buiten de modellen om.
 */
class ClearPublicCacheCommand extends Command
{
    protected $signature = 'public:cache-clear';

    protected $description = 'Laat alle gecachte publiekspagina\'s vervallen en purget het CDN';

    public function handle(): int
    {
        PublicCache::bump();
        PublicCache::purgeCdnIfDirty();

        $this->info('Paginacache ververst (generatie '.PublicCache::generation().').');

        return self::SUCCESS;
    }
}
