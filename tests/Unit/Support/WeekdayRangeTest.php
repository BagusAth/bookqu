<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\WeekdayRange;
use PHPUnit\Framework\TestCase;

class WeekdayRangeTest extends TestCase
{
    public function test_three_or_more_consecutive_days_become_a_range(): void
    {
        $this->assertSame('Sen–Jum', WeekdayRange::label([1, 2, 3, 4, 5]));
        $this->assertSame('Sen–Rab, Jum', WeekdayRange::label(['5', '3', '1', '2']));
    }

    public function test_one_or_two_days_are_listed_and_all_seven_read_every_day(): void
    {
        $this->assertSame('Sab, Min', WeekdayRange::label([6, 7]));
        $this->assertSame('Sen, Rab', WeekdayRange::label([1, 3]));
        $this->assertSame('Setiap hari', WeekdayRange::label([7, 6, 5, 4, 3, 2, 1]));
    }

    public function test_unknown_and_repeated_days_are_ignored(): void
    {
        $this->assertSame('Sel', WeekdayRange::label([2, 2, 0, 8]));
        $this->assertSame('', WeekdayRange::label([]));
    }
}
