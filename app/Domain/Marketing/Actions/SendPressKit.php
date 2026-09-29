<?php

namespace App\Domain\Marketing\Actions;

use App\Domain\Marketing\Models\MediaContact;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Marketing\Notifications\PressKitNotification;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Stuurt de embargo-perskit naar de mediacontacten van de provincie (plus landelijke contacten)
 * via een tijdelijke, ondertekende link. Landelijke berichten gaan naar iedereen.
 */
final class SendPressKit
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(PressRelease $release, ?User $by = null): int
    {
        $contacts = MediaContact::query()
            ->when($release->province_id !== null, fn ($query) => $query->where(fn ($q) => $q->whereNull('province_id')->orWhere('province_id', $release->province_id)))
            ->get();

        $expiresAt = now()->addDays((int) config('press.kit_link_days', 14));

        foreach ($contacts as $contact) {
            $url = URL::temporarySignedRoute('pers.kit', $expiresAt, ['pressRelease' => $release->slug]);

            Notification::route('mail', $contact->email)->notify(new PressKitNotification($release, $contact, $url));
        }

        $release->forceFill(['sent_at' => now()])->save();
        $this->audit->record('press_release.kit_sent', $release, ['contacts' => $contacts->count()], $by);

        return $contacts->count();
    }
}
