<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Enums\SponsorStatus;
use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Commerce\Models\Placement;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Commerce\Notifications\SponsorLinkRequestedNotification;
use App\Domain\Commerce\Notifications\SponsorMail;
use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\StaffNotifier;
use App\Filament\Resources\Sponsors\SponsorResource;
use App\Support\Mail\SendOnce;

/**
 * Betaalde sponsororder: bevestiging met factuur aan de sponsor, het akkoordverzoek aan de
 * gekozen deelnemers en een melding voor de controle door de organisatie.
 */
final class ConfirmSponsorOrder
{
    public function __construct(
        private readonly SendOnce $mail,
        private readonly StaffNotifier $staff,
    ) {}

    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        $sponsor = $order->orderable;

        if (! $sponsor instanceof Sponsor) {
            return;
        }

        if (filled($sponsor->contact_email)) {
            $this->mail->send($sponsor->contact_email, new SponsorMail(SponsorMail::PAID, $sponsor, order: $order), "order:{$order->getKey()}:sponsor-paid");
        }

        $placementIds = Placement::query()->whereIn('order_line_id', $order->lines()->pluck('id'))->pluck('id');

        SponsorLink::query()
            ->whereIn('placement_id', $placementIds)
            ->whereNull('confirmed_by_company_at')
            ->whereNull('declined_at')
            ->with(['company', 'sponsor'])
            ->each(function (SponsorLink $link): void {
                foreach ($link->company->owners() as $owner) {
                    $this->mail->send($owner, new SponsorLinkRequestedNotification($link), "link:{$link->getKey()}:requested");
                }
            });

        if ($sponsor->status === SponsorStatus::Pending) {
            $this->staff->notify(
                [StaffRole::Communication, StaffRole::Finance],
                "Sponsor betaald, wacht op controle: {$sponsor->name}",
                'Controleer logo en gegevens en zet de vermelding online.',
                SponsorResource::getUrl('edit', ['record' => $sponsor]),
                null,
                'heroicon-o-sparkles',
                'warning',
            );
        }
    }
}
