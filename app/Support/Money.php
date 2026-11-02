<?php

namespace App\Support;

class Money
{
    /** Rappen → "1'234.50" (ohne Währung). */
    public static function format(int|float|null $rappen, bool $withCurrency = false): string
    {
        $value = number_format(((float) $rappen) / 100, 2, '.', "\u{2019}");

        return $withCurrency ? 'CHF '.$value : $value;
    }

    /** "1'234.50" / "1234,5" → Rappen */
    public static function parse(string|int|float|null $input): int
    {
        if ($input === null || $input === '') {
            return 0;
        }

        $clean = str_replace(["'", "\u{2019}", ' ', 'CHF'], '', (string) $input);
        $clean = str_replace(',', '.', $clean);

        return (int) round(((float) $clean) * 100);
    }
}
