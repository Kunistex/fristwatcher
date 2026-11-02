<?php

namespace Tests\Unit;

use App\Support\FixedDates;
use App\Support\Money;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FixedDatesTest extends TestCase
{
    #[Test]
    public function parst_und_formatiert_termine(): void
    {
        $this->assertSame(['12-31'], FixedDates::parse('31.12.'));
        $this->assertSame(['06-30:3', '12-31'], FixedDates::parse('30.06.:3, 31.12.'));
        $this->assertSame(['03-31'], FixedDates::parse('31.3'));
        $this->assertSame([], FixedDates::parse('31.02.'));
        $this->assertSame('30.06.:3, 31.12.', FixedDates::format(['06-30:3', '12-31']));
    }

    #[Test]
    public function rechnet_franken_in_rappen(): void
    {
        $this->assertSame(7990, Money::parse('79.90'));
        $this->assertSame(123450, Money::parse("1'234.50"));
        $this->assertSame(1250, Money::parse('12,5'));
        $this->assertSame("1\u{2019}234.50", Money::format(123450));
    }
}
