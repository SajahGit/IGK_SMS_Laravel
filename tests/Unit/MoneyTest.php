<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_arithmetic_is_exact_to_two_decimals(): void
    {
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
        $this->assertSame('7.50', Money::sub('10.00', '2.50'));
        $this->assertSame('7.50', Money::mul('2.50', '3'));
        $this->assertSame('2.50', Money::div('10', '4'));
    }

    public function test_division_by_zero_returns_zero(): void
    {
        $this->assertSame('0.00', Money::div('10', '0'));
    }

    public function test_null_and_empty_are_treated_as_zero(): void
    {
        $this->assertSame('1.00', Money::add(null, '1'));
        $this->assertSame('0.00', Money::add('', null));
    }

    public function test_comparison_and_formatting(): void
    {
        $this->assertSame(1, Money::comp('2.00', '1.00'));
        $this->assertSame(0, Money::comp('5.00', '5.00'));
        $this->assertSame(-1, Money::comp('1.00', '2.00'));
        $this->assertSame('LKR 1,234.50', Money::format(1234.5));
    }
}
