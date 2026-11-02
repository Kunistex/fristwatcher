<?php

namespace App\Services;

use App\Enums\NoticeAnchor;
use App\Enums\NoticeUnit;
use App\Models\Contract;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Berechnet pro Vertrag:
 *  - termEnd:         das nächste Laufzeitende, auf das noch fristgerecht gekündigt werden kann
 *  - receiptDeadline: letzter Tag, an dem die Kündigung beim Anbieter eingetroffen sein muss
 *                     (Wochenende/Feiertag → letzter Arbeitstag davor)
 *  - sendBy:          empfohlener Versandtag (receiptDeadline minus Puffer in Arbeitstagen)
 */
class DeadlineCalculator
{
    private const MAX_PERIODS = 600;

    public function __construct(private readonly HolidayCalendar $holidays) {}

    public function forContract(Contract $contract, ?CarbonImmutable $today = null, ?User $user = null): DeadlineResult
    {
        return $this->calculate([
            'start_date' => $contract->start_date,
            'min_term_months' => $contract->min_term_months,
            'renewal_months' => $contract->renewal_months,
            'notice_anchor' => $contract->notice_anchor,
            'fixed_end_dates' => $contract->fixed_end_dates,
            'notice_value' => $contract->notice_value,
            'notice_unit' => $contract->notice_unit,
            'send_buffer_days' => $contract->send_buffer_days ?? $user?->sendBufferDays(),
        ], $today, $user?->canton ?? 'SG');
    }

    /**
     * @param  array{start_date?: mixed, min_term_months?: ?int, renewal_months?: ?int, notice_anchor?: mixed,
     *               fixed_end_dates?: ?array, notice_value?: ?int, notice_unit?: mixed, send_buffer_days?: ?int}  $input
     */
    public function calculate(array $input, ?CarbonImmutable $today = null, string $canton = 'SG'): DeadlineResult
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();

        $anchor = $input['notice_anchor'] instanceof NoticeAnchor
            ? $input['notice_anchor']
            : (NoticeAnchor::tryFrom((string) ($input['notice_anchor'] ?? '')) ?? NoticeAnchor::TermEnd);
        $unit = $input['notice_unit'] instanceof NoticeUnit
            ? $input['notice_unit']
            : (NoticeUnit::tryFrom((string) ($input['notice_unit'] ?? '')) ?? NoticeUnit::Months);
        $noticeValue = max(0, (int) ($input['notice_value'] ?? 0));
        $buffer = max(0, (int) ($input['send_buffer_days'] ?? config('fristwatcher.default_send_buffer_days')));

        $receiptFor = fn (CarbonImmutable $end, ?int $monthsOverride = null) => $monthsOverride !== null
            ? $this->receiptDeadline($end, $monthsOverride, NoticeUnit::Months, $canton)
            : $this->receiptDeadline($end, $noticeValue, $unit, $canton);

        return match ($anchor) {
            NoticeAnchor::Anytime => new DeadlineResult(note: 'Jederzeit kündbar mit '.$this->noticeText($noticeValue, $unit).'.'),
            NoticeAnchor::FixedDates => $this->forFixedDates($input['fixed_end_dates'] ?? [], $today, $receiptFor, $buffer, $canton),
            NoticeAnchor::TermEnd => $this->forTermEnd($input, $today, $receiptFor, $buffer, $canton),
        };
    }

    public function receiptDeadline(CarbonImmutable $termEnd, int $value, NoticeUnit $unit, string $canton = 'SG'): CarbonImmutable
    {
        $raw = match ($unit) {
            NoticeUnit::Days => $termEnd->subDays($value),
            NoticeUnit::Weeks => $termEnd->subWeeks($value),
            NoticeUnit::Months => $termEnd->subMonthsNoOverflow($value),
        };

        // "Auf Ende Monat mit 3 Monaten Frist": 30.06. → Eingang bis 31.03., nicht 30.03.
        if ($unit === NoticeUnit::Months && $value > 0 && $termEnd->isLastOfMonth()) {
            $raw = $raw->endOfMonth()->startOfDay();
        }

        return $this->holidays->previousBusinessDay($raw, $canton);
    }

    private function forTermEnd(array $input, CarbonImmutable $today, callable $receiptFor, int $buffer, string $canton): DeadlineResult
    {
        $start = $this->date($input['start_date'] ?? null);
        if (! $start) {
            return new DeadlineResult(note: 'Vertragsbeginn fehlt.');
        }

        $min = (int) ($input['min_term_months'] ?? 0);
        $renewal = (int) ($input['renewal_months'] ?? 0);

        if ($min <= 0 && $renewal <= 0) {
            return new DeadlineResult(note: 'Weder Mindestlaufzeit noch Verlängerung erfasst.');
        }

        $first = $min > 0 ? $min : $renewal;
        $lastEnd = null;

        for ($k = 0; $k < self::MAX_PERIODS; $k++) {
            $months = $first + $k * $renewal;
            $end = $start->addMonthsNoOverflow($months)->subDay();
            $lastEnd = $end;

            if ($end->lt($today)) {
                if ($renewal <= 0) {
                    break;
                }

                continue;
            }

            $receipt = $receiptFor($end);
            if ($receipt->gte($today)) {
                return $this->result($end, $receipt, $buffer, $today, $canton);
            }

            if ($renewal <= 0) {
                return new DeadlineResult(termEnd: $end, note: 'Frist verpasst; der Vertrag endet ohne Verlängerung.');
            }
        }

        if ($renewal <= 0 && $lastEnd?->lt($today)) {
            return new DeadlineResult(termEnd: $lastEnd, note: 'Laufzeit ist abgelaufen, keine automatische Verlängerung.');
        }

        return new DeadlineResult(note: 'Kein Kündigungstermin gefunden.');
    }

    /**
     * @param  list<string>|null  $fixed  "MM-DD" oder "MM-DD:N" (N = eigene Frist in Monaten für diesen Termin)
     */
    private function forFixedDates(?array $fixed, CarbonImmutable $today, callable $receiptFor, int $buffer, string $canton): DeadlineResult
    {
        $parsed = [];
        foreach ((array) $fixed as $entry) {
            if (is_string($entry) && preg_match('/^(\d{2})-(\d{2})(?::(\d{1,2}))?$/', $entry, $m)) {
                $parsed[] = [(int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : null];
            }
        }

        if ($parsed === []) {
            return new DeadlineResult(note: 'Kein fester Kündigungstermin erfasst.');
        }

        $candidates = [];
        for ($year = $today->year; $year <= $today->year + 3; $year++) {
            foreach ($parsed as [$month, $day, $override]) {
                if (checkdate($month, $day, $year)) {
                    $candidates[] = [CarbonImmutable::create($year, $month, $day)->startOfDay(), $override];
                }
            }
        }

        usort($candidates, fn ($a, $b) => $a[0] <=> $b[0]);

        foreach ($candidates as [$end, $override]) {
            if ($end->lt($today)) {
                continue;
            }
            $receipt = $receiptFor($end, $override);
            if ($receipt->gte($today)) {
                return $this->result($end, $receipt, $buffer, $today, $canton);
            }
        }

        return new DeadlineResult(note: 'Kein Kündigungstermin gefunden.');
    }

    private function result(CarbonImmutable $end, CarbonImmutable $receipt, int $buffer, CarbonImmutable $today, string $canton): DeadlineResult
    {
        $sendBy = $this->holidays->subBusinessDays($receipt, $buffer, $canton);
        $note = null;

        if ($sendBy->lt($today)) {
            $sendBy = $today;
            $note = 'Knapp: heute versenden, am besten per E-Mail oder persönlich.';
        }

        return new DeadlineResult($end, $receipt, $sendBy, $note);
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->startOfDay();
        }
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value)->startOfDay();
        }
        if (is_string($value) && $value !== '') {
            try {
                return CarbonImmutable::parse($value)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function noticeText(int $value, NoticeUnit $unit): string
    {
        return $value === 0 ? 'sofortiger Wirkung' : $value.' '.$unit->label($value).' Frist';
    }
}
