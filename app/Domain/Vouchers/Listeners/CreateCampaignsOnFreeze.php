<?php

namespace App\Domain\Vouchers\Listeners;

use App\Domain\Ranking\Events\EditionFrozen;
use App\Domain\Vouchers\Actions\CreateVoucherCampaigns;

final class CreateCampaignsOnFreeze
{
    public function __construct(private readonly CreateVoucherCampaigns $create) {}

    public function handle(EditionFrozen $event): void
    {
        ($this->create)($event->edition);
    }
}
