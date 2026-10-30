<?php

namespace App\Support\Mail;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Verstuurt een statusmail hooguit één keer per onderwerp en ontvanger. Een herhaald webhookevent of
 * een dubbele statuswijziging levert zo nooit een tweede mail op (briefing 29 okt §11).
 *
 * De sleutel wordt eerst vastgelegd en pas daarna wordt er verstuurd; twee gelijktijdige aanroepen
 * kunnen dus niet allebei mailen.
 */
final class SendOnce
{
    /**
     * @param  object|string  $notifiable  Een model met Notifiable, of een los e-mailadres.
     * @param  string  $subject  Wat de mail uniek maakt, bijvoorbeeld "order:12" of "entry:5:received".
     */
    public function send(object|string $notifiable, Notification $notification, string $subject): bool
    {
        $recipient = $this->recipient($notifiable);

        if ($recipient === '') {
            return false;
        }

        try {
            DB::table('sent_notifications')->insert([
                'key' => hash('sha256', $notification::class.'|'.$subject.'|'.$recipient),
                'notification' => class_basename($notification),
                'recipient' => mb_substr($recipient, 0, 191),
                'sent_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        if (is_string($notifiable)) {
            NotificationFacade::route('mail', $notifiable)->notify($notification);
        } else {
            $notifiable->notify($notification);
        }

        return true;
    }

    public function alreadySent(object|string $notifiable, string $notificationClass, string $subject): bool
    {
        return DB::table('sent_notifications')
            ->where('key', hash('sha256', $notificationClass.'|'.$subject.'|'.$this->recipient($notifiable)))
            ->exists();
    }

    private function recipient(object|string $notifiable): string
    {
        if (is_string($notifiable)) {
            return mb_strtolower(trim($notifiable));
        }

        if ($notifiable instanceof AnonymousNotifiable) {
            return mb_strtolower((string) ($notifiable->routes['mail'] ?? ''));
        }

        return $notifiable::class.':'.$notifiable->getKey();
    }
}
