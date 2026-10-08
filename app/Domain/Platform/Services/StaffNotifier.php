<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\Enums\StaffRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Meldingen voor medewerkers in de bel van de backoffice, per rol. Stuurt alleen naar actieve
 * accounts en laat de veroorzaker zelf buiten beschouwing.
 */
final class StaffNotifier
{
    /**
     * @param  list<StaffRole>  $roles
     * @return int aantal medewerkers dat de melding kreeg
     */
    public function notify(
        array $roles,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?User $except = null,
        string $icon = 'heroicon-o-bell',
        string $color = 'primary',
    ): int {
        $roleNames = array_map(fn (StaffRole $role): string => $role->value, $roles);

        // Geen role()-scope: die gooit een fout zolang een rol nog niet bestaat (bijvoorbeeld in tests).
        $users = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roleNames))
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->get();

        if ($users->isEmpty()) {
            return 0;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->iconColor($color);

        if ($url !== null) {
            $notification->actions([
                Action::make('open')->label('Openen')->url($url)->markAsRead(),
            ]);
        }

        $notification->sendToDatabase($users);

        return $users->count();
    }
}
