<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ReportPeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_this_month_is_compared_up_to_today_against_the_same_days_last_month(): void
    {
        CarbonImmutable::setTestNow('2026-11-20 10:00:00');

        $compared = ReportPeriod::fromQuery(['periode' => 'bulan-ini'])->toDate();
        $previous = $compared->previous();

        $this->assertSame('2026-11-01 – 2026-11-20', $this->span($compared));
        $this->assertSame('2026-10-01 – 2026-10-20', $this->span($previous));
    }

    public function test_the_stretch_never_runs_past_the_end_of_a_shorter_previous_month(): void
    {
        CarbonImmutable::setTestNow('2026-03-31 10:00:00');

        $previous = ReportPeriod::fromQuery(['periode' => 'bulan-ini'])->toDate()->previous();

        $this->assertSame('2026-02-01 – 2026-02-28', $this->span($previous));
    }

    public function test_the_last_three_months_are_compared_up_to_the_same_day(): void
    {
        CarbonImmutable::setTestNow('2026-11-20 10:00:00');

        $previous = ReportPeriod::fromQuery(['periode' => '3-bulan'])->toDate()->previous();

        $this->assertSame('2026-06-01 – 2026-08-20', $this->span($previous));
    }

    public function test_a_period_that_already_ended_keeps_the_whole_previous_period(): void
    {
        CarbonImmutable::setTestNow('2026-11-20 10:00:00');
        $lastMonth = ReportPeriod::fromQuery(['periode' => 'bulan-lalu']);

        $this->assertSame($lastMonth, $lastMonth->toDate());
        $this->assertSame('2026-09-01 – 2026-09-30', $this->span($lastMonth->previous()));
    }

    private function span(ReportPeriod $period): string
    {
        return $period->start->format('Y-m-d').' – '.$period->end->format('Y-m-d');
    }
}
