<?php

namespace Tests\Unit;

use App\Services\DeadlineCalculator;
use App\Services\HolidayCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeadlineCalculatorTest extends TestCase
{
    private DeadlineCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = app(DeadlineCalculator::class);
    }

    private function d(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date);
    }

    #[Test]
    public function kvg_grundversicherung_2026_eingang_bis_30_november(): void
    {
        $r = $this->calc->calculate([
            'notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['12-31'],
            'notice_value' => 1, 'notice_unit' => 'months', 'send_buffer_days' => 5,
        ], $this->d('2026-11-02'));

        $this->assertSame('2026-12-31', $r->termEnd->toDateString());
        $this->assertSame('2026-11-30', $r->receiptDeadline->toDateString()); // Montag
        $this->assertSame('2026-11-23', $r->sendBy->toDateString());
    }

    #[Test]
    public function kvg_2025_wochenende_verschiebt_auf_freitag_28_november(): void
    {
        $r = $this->calc->calculate([
            'notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['12-31'],
            'notice_value' => 1, 'notice_unit' => 'months',
        ], $this->d('2025-10-01'));

        $this->assertSame('2025-11-28', $r->receiptDeadline->toDateString());
    }

    #[Test]
    public function nach_verpasster_frist_springt_auf_naechstes_jahr(): void
    {
        $r = $this->calc->calculate([
            'notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['12-31'],
            'notice_value' => 1, 'notice_unit' => 'months',
        ], $this->d('2026-12-01'));

        $this->assertSame('2027-12-31', $r->termEnd->toDateString());
        $this->assertSame('2027-11-30', $r->receiptDeadline->toDateString());
    }

    #[Test]
    public function franchise_300_juni_termin_mit_eigener_frist_von_drei_monaten(): void
    {
        $r = $this->calc->calculate([
            'notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['06-30:3', '12-31'],
            'notice_value' => 1, 'notice_unit' => 'months',
        ], $this->d('2026-12-15'));

        // 31.12.2026 nicht mehr erreichbar (Eingang bis 30.11.), also 30.6.2027 mit Eingang bis 31.3.2027.
        $this->assertSame('2027-06-30', $r->termEnd->toDateString());
        $this->assertSame('2027-03-31', $r->receiptDeadline->toDateString());
    }

    #[Test]
    public function fitnessabo_mit_mindestlaufzeit_und_jaehrlicher_verlaengerung(): void
    {
        $r = $this->calc->calculate([
            'notice_anchor' => 'term_end', 'start_date' => '2025-03-01',
            'min_term_months' => 12, 'renewal_months' => 12,
            'notice_value' => 2, 'notice_unit' => 'months', 'send_buffer_days' => 5,
        ], $this->d('2026-11-02'));

        // Ende 1. Periode 28.02.2026 vorbei, nächste Periode endet 28.02.2027.
        $this->assertSame('2027-02-28', $r->termEnd->toDateString());
        // Monatsende minus 2 Monate → 31.12.2026 = Feiertag? Nein, Silvester ist Arbeitstag.
        $this->assertSame('2026-12-31', $r->receiptDeadline->toDateString());
        // 5 Arbeitstage zurück, 25./26.12. sind Feiertage: 30, 29, 28, 24, 23.
        $this->assertSame('2026-12-23', $r->sendBy->toDateString());
    }

    #[Test]
    public function ohne_verlaengerung_und_verpasster_frist_gibt_es_keinen_eingangstag(): void
    {
        $r = $this->calc->calculate([
            'notice_anchor' => 'term_end', 'start_date' => '2026-01-01',
            'min_term_months' => 12, 'renewal_months' => null,
            'notice_value' => 3, 'notice_unit' => 'months',
        ], $this->d('2026-11-02'));

        $this->assertSame('2026-12-31', $r->termEnd->toDateString());
        $this->assertNull($r->receiptDeadline);
        $this->assertNotNull($r->note);
    }

    #[Test]
    public function streaming_ohne_frist_kuendigung_bis_vor_abrechnung(): void
    {
        $r = $this->calc->calculate([
            'notice_anchor' => 'term_end', 'start_date' => '2026-01-15',
            'min_term_months' => 1, 'renewal_months' => 1,
            'notice_value' => 0, 'notice_unit' => 'days', 'send_buffer_days' => 1,
        ], $this->d('2026-11-02'));

        $this->assertSame('2026-11-14', $r->termEnd->toDateString());
        $this->assertSame('2026-11-13', $r->receiptDeadline->toDateString()); // 14.11. ist Samstag
    }

    #[Test]
    public function knapper_versandtag_wird_auf_heute_gesetzt(): void
    {
        $r = $this->calc->calculate([
            'notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['12-31'],
            'notice_value' => 1, 'notice_unit' => 'months', 'send_buffer_days' => 5,
        ], $this->d('2026-11-27'));

        $this->assertSame('2026-11-27', $r->sendBy->toDateString());
        $this->assertNotNull($r->note);
    }

    #[Test]
    public function jederzeit_kuendbar_hat_keinen_termin(): void
    {
        $r = $this->calc->calculate(['notice_anchor' => 'anytime', 'notice_value' => 1, 'notice_unit' => 'months'], $this->d('2026-11-02'));

        $this->assertNull($r->termEnd);
        $this->assertNull($r->receiptDeadline);
    }

    #[Test]
    public function ostern_und_feiertage(): void
    {
        $this->assertSame('2026-04-05', HolidayCalendar::easterSunday(2026)->toDateString());
        $this->assertSame('2027-03-28', HolidayCalendar::easterSunday(2027)->toDateString());

        $cal = app(HolidayCalendar::class);
        $this->assertTrue($cal->isHoliday($this->d('2026-04-03'), 'SG')); // Karfreitag
        $this->assertTrue($cal->isHoliday($this->d('2026-05-14'), 'SG')); // Auffahrt
        $this->assertTrue($cal->isHoliday($this->d('2026-11-01'), 'SG')); // Allerheiligen
        $this->assertFalse($cal->isHoliday($this->d('2026-11-01'), 'ZH'));
    }
}
