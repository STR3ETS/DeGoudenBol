<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Models\NewsletterSubscription;
use App\Domain\Platform\Notifications\NewsletterConfirmNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Dubbele opt-in: inschrijving pas actief na klik op de ondertekende bevestigingslink.
 */
final class SubscribeToNewsletter
{
    public function __invoke(string $email, string $source = 'site', ?string $ip = null): NewsletterSubscription
    {
        $email = Str::lower(trim($email));

        $subscription = NewsletterSubscription::query()->firstOrNew(['email' => $email]);

        if ($subscription->exists && $subscription->isActive()) {
            return $subscription;
        }

        $subscription->fill(['source' => $source, 'ip' => $ip, 'unsubscribed_at' => null])->save();

        $url = URL::temporarySignedRoute('nieuwsbrief.bevestigen', now()->addDays(3), ['subscription' => $subscription->getKey()]);

        Notification::route('mail', $email)->notify(new NewsletterConfirmNotification($url));

        return $subscription;
    }
}
