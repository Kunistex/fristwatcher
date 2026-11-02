<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Active = 'active';
    case CancellationPlanned = 'cancellation_planned';
    case CancelledUnconfirmed = 'cancelled_unconfirmed';
    case CancelledConfirmed = 'cancelled_confirmed';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::CancellationPlanned => 'Kündigung geplant',
            self::CancelledUnconfirmed => 'Gekündigt, Bestätigung offen',
            self::CancelledConfirmed => 'Gekündigt und bestätigt',
            self::Ended => 'Beendet',
        };
    }

    /** Verträge, für die Kündigungsfristen noch relevant sind. */
    public function watchesDeadline(): bool
    {
        return in_array($this, [self::Active, self::CancellationPlanned], true);
    }

    public function isCancelled(): bool
    {
        return in_array($this, [self::CancelledUnconfirmed, self::CancelledConfirmed], true);
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::Active, self::CancellationPlanned, self::CancelledUnconfirmed, self::CancelledConfirmed];
    }
}
