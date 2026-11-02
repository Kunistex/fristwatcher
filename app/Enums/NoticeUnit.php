<?php

namespace App\Enums;

enum NoticeUnit: string
{
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';

    public function label(int $value = 2): string
    {
        return match ($this) {
            self::Days => $value === 1 ? 'Tag' : 'Tage',
            self::Weeks => $value === 1 ? 'Woche' : 'Wochen',
            self::Months => $value === 1 ? 'Monat' : 'Monate',
        };
    }
}
