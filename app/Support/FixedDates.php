<?php

namespace App\Support;

/**
 * Feste Kündigungstermine zwischen Formular ("31.12., 30.06.:3") und Speicherformat (["12-31", "06-30:3"]).
 * ":3" hinter einem Termin bedeutet eine eigene Frist von 3 Monaten für genau diesen Termin.
 */
class FixedDates
{
    /** @return list<string> */
    public static function parse(?string $input): array
    {
        if (! $input) {
            return [];
        }

        $out = [];
        foreach (preg_split('/[;,\s]+(?=\d)/', trim($input)) as $part) {
            if (preg_match('/^(\d{1,2})\.(\d{1,2})\.?(?:\s*:\s*(\d{1,2}))?$/', trim($part), $m)
                && checkdate((int) $m[2], (int) $m[1], 2024)) {
                $value = sprintf('%02d-%02d', $m[2], $m[1]);
                if (! empty($m[3])) {
                    $value .= ':'.(int) $m[3];
                }
                $out[] = $value;
            }
        }

        return array_values(array_unique($out));
    }

    /** @param  list<string>|null  $dates */
    public static function format(?array $dates): string
    {
        return collect($dates ?? [])->map(function (string $v) {
            [$md, $override] = array_pad(explode(':', $v, 2), 2, null);
            [$m, $d] = explode('-', $md);

            return sprintf('%02d.%02d.', $d, $m).($override ? ':'.$override : '');
        })->implode(', ');
    }

    /** "31.12." / "30.6. (3 Mte.)" für Anzeigen */
    public static function humanize(?array $dates): string
    {
        return collect($dates ?? [])->map(function (string $v) {
            [$md, $override] = array_pad(explode(':', $v, 2), 2, null);
            [$m, $d] = explode('-', $md);

            return ((int) $d).'.'.((int) $m).'.'.($override ? " ({$override} Mt. Frist)" : '');
        })->implode(', ');
    }
}
