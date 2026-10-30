<?php

namespace App\Domain\Intake\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Statussen van de ophaalservice (briefing 29 okt §5): aangevraagd, betaald, ingepland, onderweg,
 * opgehaald, ontvangen. Pas actief na betaling.
 */
enum PickupStatus: string implements HasColor, HasLabel
{
    case Requested = 'requested';
    case Paid = 'paid';
    case Scheduled = 'scheduled';
    case EnRoute = 'en_route';
    case PickedUp = 'picked_up';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Aangevraagd (nog niet betaald)',
            self::Paid => 'Betaald',
            self::Scheduled => 'Ingepland',
            self::EnRoute => 'Onderweg',
            self::PickedUp => 'Opgehaald',
            self::Received => 'Ontvangen',
            self::Cancelled => 'Geannuleerd',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Paid => 'info',
            self::Scheduled, self::EnRoute => 'primary',
            self::PickedUp, self::Received => 'success',
            self::Cancelled => 'gray',
        };
    }

    /**
     * Telt mee voor de capaciteit van de vrijdag (ook een onbetaalde aanvraag houdt de plek even vast).
     *
     * @return list<self>
     */
    public static function occupying(): array
    {
        return [self::Requested, self::Paid, self::Scheduled, self::EnRoute, self::PickedUp, self::Received];
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Paid, self::Scheduled, self::EnRoute, self::PickedUp, self::Received], true);
    }

    /**
     * Mag de deelnemer de vrijdag nog wijzigen (binnen de termijn)?
     */
    public function isChangeable(): bool
    {
        return in_array($this, [self::Requested, self::Paid, self::Scheduled], true);
    }

    /**
     * Volgende stap voor de chauffeur of de ontvangst in de backoffice.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Paid => self::Scheduled,
            self::Scheduled => self::EnRoute,
            self::EnRoute => self::PickedUp,
            self::PickedUp => self::Received,
            default => null,
        };
    }
}
