<?php

namespace Tests\Unit;

use App\Support\NumberToWords;
use PHPUnit\Framework\TestCase;

class NumberToWordsTest extends TestCase
{
    public function test_it_formats_rupees_and_cents_in_words(): void
    {
        $this->assertSame('zero rupees and zero cents only', NumberToWords::rupees('0.00'));
        $this->assertSame('one thousand two hundred thirty-four rupees and fifty-six cents only', NumberToWords::rupees('1234.56'));
        $this->assertSame('one lakh rupees and five cents only', NumberToWords::rupees('100000.05'));
    }
}
