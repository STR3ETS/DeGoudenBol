<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Commerce\Models\VisibilitySpot;
use App\Domain\Commerce\Notifications\VisibilitySpotConfirmedNotification;
use App\Support\Mail\SendOnce;

/**
 * Betaald: de zichtbaarheidsplek staat vast en komt op de site. De bakker krijgt een bevestiging.
 */
final class ActivateVisibilitySpot
{
    public function __construct(private readonly SendOnce $mail) {}

    public function handle(OrderPaid $event): void
    {
        $spot = VisibilitySpot::query()->where('order_id', $event->order->getKey())->whereNull('paid_at')->first();

        if ($spot === null) {
            return;
        }

        $spot->forceFill(['paid_at' => now(), 'reserved_until' => null])->save();

        foreach ($spot->company->owners() as $owner) {
            $this->mail->send($owner, new VisibilitySpotConfirmedNotification($spot), "visibility-spot:{$spot->getKey()}:paid");
        }
    }
}
