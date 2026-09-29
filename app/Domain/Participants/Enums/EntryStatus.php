<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * De negen stappen uit de briefing (hoofdstuk 5) plus de zijpaden.
 */
enum EntryStatus: string implements HasColor, HasLabel
{
    case PendingPayment = 'pending_payment';
    case Registered = 'registered';
    case Scheduled = 'scheduled';
    case Received = 'received';
    case Numbered = 'numbered';
    case Scored = 'scored';
    case Reviewed = 'reviewed';
    case Linked = 'linked';
    case Published = 'published';
    case Confidential = 'confidential';
    case FreshnessExpired = 'freshness_expired';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => 'Wacht op betaling',
            self::Registered => 'Aangemeld',
            self::Scheduled => 'Ingepland',
            self::Received => 'Ontvangen',
            self::Numbered => 'Testnummer toegekend',
            self::Scored => 'Beoordeeld',
            self::Reviewed => 'Scorecontrole klaar',
            self::Linked => 'Gekoppeld',
            self::Published => 'Gepubliceerd',
            self::Confidential => 'Vertrouwelijk teruggekoppeld',
            self::FreshnessExpired => 'Versheid verlopen',
            self::Withdrawn => 'Teruggetrokken',
            self::Cancelled => 'Geannuleerd',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Registered, self::Scheduled, self::Received, self::Numbered => 'info',
            self::Scored, self::Reviewed, self::Linked => 'primary',
            self::Published => 'success',
            self::Confidential => 'gray',
            self::FreshnessExpired => 'danger',
            self::Withdrawn, self::Cancelled => 'gray',
        };
    }

    /**
     * Telt deze inschrijving mee voor de capaciteit van de provincie?
     */
    public function occupiesPlace(): bool
    {
        return ! in_array($this, [self::Withdrawn, self::Cancelled], true);
    }

    /**
     * @return list<self>
     */
    public static function occupying(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->occupiesPlace()));
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }

    /**
     * Stap in de tijdlijn van het portaal (1 t/m 4), null voor zijpaden.
     */
    public function timelineStep(): ?int
    {
        return match ($this) {
            self::PendingPayment => 0,
            self::Registered => 1,
            self::Scheduled, self::Received, self::Numbered => 2,
            self::Scored, self::Reviewed, self::Linked => 3,
            self::Published, self::Confidential => 4,
            default => null,
        };
    }
}
