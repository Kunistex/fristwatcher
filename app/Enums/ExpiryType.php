<?php

namespace App\Enums;

enum ExpiryType: string
{
    case Passport = 'passport';
    case IdCard = 'id_card';
    case DrivingLicense = 'driving_license';
    case Warranty = 'warranty';
    case Vignette = 'vignette';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Passport => 'Pass',
            self::IdCard => 'Identitätskarte',
            self::DrivingLicense => 'Führerausweis',
            self::Warranty => 'Garantie',
            self::Vignette => 'Vignette',
            self::Other => 'Sonstiges',
        };
    }

    /** @return list<int> */
    public function offsets(): array
    {
        return config("fristwatcher.expiry_offsets.{$this->value}", [90, 30]);
    }
}
