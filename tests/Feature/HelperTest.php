<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HelperTest extends TestCase
{
    public function testGetMonthDayStart()
    {
        list($monthStart, $monthEnd) = getMonthDayStart('2021-11-15');
        $this->assertEquals($monthStart->format('Y-m-d'), '2021-11-01');
        $this->assertEquals($monthEnd->format('Y-m-d'), '2021-11-30');

        $exceptedMonthStart = now()->startOfMonth()->format('Y-m-d');
        $exceptedMonthEnd = now()->endOfMonth()->format('Y-m-d');

        list($monthStart, $monthEnd) = getMonthDayStart(now()->format('Y-m-d'));
        $this->assertEquals($monthStart->format('Y-m-d'), $exceptedMonthStart);
        $this->assertEquals($monthEnd->format('Y-m-d'), $exceptedMonthEnd);
    }

    public function testReplceKeyWithValueFromText() {
        $pairs = [
            '[asdf]' => '1',
            '[po]' => '2',
            '[zzz]' => '3',
        ];
        $text = '[asdf] [po] [zzz]';
        $result = replceKeyWithValueFromText($text, $pairs);
        $this->assertEQuals($result, '1 2 3');
    }
}
