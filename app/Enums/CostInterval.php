<?php

namespace App\Enums;

enum CostInterval: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case HalfYearly = 'half_yearly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'pro Monat',
            self::Quarterly => 'pro Quartal',
            self::HalfYearly => 'pro Halbjahr',
            self::Yearly => 'pro Jahr',
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::HalfYearly => 6,
            self::Yearly => 12,
        };
    }

    /** Betrag in Rappen auf einen Monat umgerechnet. */
    public function toMonthly(int $amount): float
    {
        return $amount / $this->months();
    }
}
