<?php

namespace Tests\Unit\Recruitment;

use App\Modules\Recruitment\Services\ApplicationEligibilityService;
use CodeIgniter\Test\CIUnitTestCase;
use DateTimeImmutable;

final class ApplicationEligibilityServiceTest extends CIUnitTestCase
{
    public function testCooldownEndsExactlyThreeCalendarMonthsAfterRejection(): void
    {
        $availableAt = ApplicationEligibilityService::availableAt(
            new DateTimeImmutable('2026-08-22 14:30:00'),
        );

        $this->assertSame('2026-11-22 14:30:00', $availableAt->format('Y-m-d H:i:s'));
    }
}
