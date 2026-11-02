<?php

namespace App\Enums;

enum NoticeAnchor: string
{
    /** Kündigung auf Ende der (Mindest-)Laufzeit, danach je Verlängerungsperiode. */
    case TermEnd = 'term_end';

    /** Kündigung nur auf feste Kalendertermine, z.B. 31.12. (Krankenkasse). */
    case FixedDates = 'fixed_dates';

    /** Jederzeit kündbar mit Frist, keine Termine. */
    case Anytime = 'anytime';

    public function label(): string
    {
        return match ($this) {
            self::TermEnd => 'Auf Ende der Laufzeit',
            self::FixedDates => 'Auf feste Termine (z.B. 31.12.)',
            self::Anytime => 'Jederzeit',
        };
    }
}
