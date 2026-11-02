<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class HolidayCalendar
{
    /** @var array<string, array<string, true>> */
    private array $cache = [];

    public function isBusinessDay(CarbonImmutable $date, string $canton = 'SG'): bool
    {
        return ! $date->isWeekend() && ! $this->isHoliday($date, $canton);
    }

    public function isHoliday(CarbonImmutable $date, string $canton = 'SG'): bool
    {
        return isset($this->holidays($date->year, $canton)[$date->format('Y-m-d')]);
    }

    /** Letzter Arbeitstag am oder vor $date. */
    public function previousBusinessDay(CarbonImmutable $date, string $canton = 'SG'): CarbonImmutable
    {
        while (! $this->isBusinessDay($date, $canton)) {
            $date = $date->subDay();
        }

        return $date;
    }

    /** $days Arbeitstage vor $date. */
    public function subBusinessDays(CarbonImmutable $date, int $days, string $canton = 'SG'): CarbonImmutable
    {
        while ($days > 0) {
            $date = $date->subDay();
            if ($this->isBusinessDay($date, $canton)) {
                $days--;
            }
        }

        return $date;
    }

    /** @return array<string, true> */
    public function holidays(int $year, string $canton = 'SG'): array
    {
        $key = $year.'-'.$canton;

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $config = config('fristwatcher.holidays');
        $dates = [];

        foreach (array_merge($config['fixed'], $config['cantonal'][$canton] ?? []) as $md) {
            $dates[sprintf('%d-%s', $year, $md)] = true;
        }

        $easter = self::easterSunday($year);
        foreach ($config['easter_offsets'] as $offset) {
            $dates[$easter->addDays($offset)->format('Y-m-d')] = true;
        }

        return $this->cache[$key] = $dates;
    }

    /** Ostersonntag (gregorianisch, anonymer Algorithmus). */
    public static function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }
}
