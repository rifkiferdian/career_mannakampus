<?php

namespace Tests\Unit\Admin;

use App\Modules\Admin\Support\AgendaPeriod;
use PHPUnit\Framework\TestCase;

class AgendaPeriodTest extends TestCase
{
    public function testDayDefaultsAndMovesOneDay(): void
    {
        $period = new AgendaPeriod('2026-09-22', 'day');
        self::assertSame('2026-09-22 00:00:00', $period->from());
        self::assertSame('2026-09-22 23:59:59', $period->until());
        self::assertSame('2026-09-21', $period->previous());
        self::assertSame('2026-09-23', $period->next());
        self::assertCount(1, $period->days());
    }

    public function testWeekStartsMondayAcrossYearBoundary(): void
    {
        $period = new AgendaPeriod('2027-01-01', 'week');
        self::assertSame('2026-12-28 00:00:00', $period->from());
        self::assertSame('2027-01-03 23:59:59', $period->until());
        self::assertSame('2026-12-21', $period->previous());
        self::assertSame('2027-01-04', $period->next());
        self::assertCount(7, $period->days());
    }

    public function testMonthCalendarIncludesAdjacentDaysAndNavigatesFromMonthStart(): void
    {
        $period = new AgendaPeriod('2026-01-31', 'month');
        self::assertSame('2025-12-29 00:00:00', $period->from());
        self::assertSame('2026-02-01 23:59:59', $period->until());
        self::assertSame('2025-12-01', $period->previous());
        self::assertSame('2026-02-01', $period->next());
        self::assertSame('Januari 2026', $period->label());
        self::assertCount(35, $period->days());
        self::assertFalse($period->days()[0]['in_month']);
        self::assertTrue($period->days()[3]['in_month']);
    }

    public function testInvalidDateAndViewFallBackToCurrentMonth(): void
    {
        $period = new AgendaPeriod('2026-02-30', 'year');
        self::assertSame('month', $period->view());
        self::assertSame(date('Y-m-d'), $period->selectedDate());
    }
}
