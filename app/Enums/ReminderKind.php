<?php

namespace App\Enums;

enum ReminderKind: string
{
    case Deadline = 'deadline';
    case Expiry = 'expiry';
    case PriceSpecial = 'price_special';
    case Confirmation = 'confirmation';
}
