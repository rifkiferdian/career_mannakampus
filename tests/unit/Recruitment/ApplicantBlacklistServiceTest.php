<?php

namespace Tests\Unit\Recruitment;

use App\Modules\Recruitment\Services\ApplicantBlacklistService;
use CodeIgniter\Test\CIUnitTestCase;
use DateTimeImmutable;

final class ApplicantBlacklistServiceTest extends CIUnitTestCase
{
    public function testTemporaryBlacklistIsActiveUntilEndTime(): void
    {
        $status = ApplicantBlacklistService::statusOf([
            'revoked_at' => null,
            'is_permanent' => 0,
            'ends_at' => '2027-08-22 23:59:59',
        ], new DateTimeImmutable('2026-08-22 10:00:00'));

        $this->assertSame('active', $status);
    }

    public function testTemporaryBlacklistExpiresAfterEndTime(): void
    {
        $status = ApplicantBlacklistService::statusOf([
            'revoked_at' => null,
            'is_permanent' => 0,
            'ends_at' => '2026-08-21 23:59:59',
        ], new DateTimeImmutable('2026-08-22 10:00:00'));

        $this->assertSame('expired', $status);
    }

    public function testPermanentBlacklistDoesNotRequireEndTime(): void
    {
        $status = ApplicantBlacklistService::statusOf([
            'revoked_at' => null,
            'is_permanent' => 1,
            'ends_at' => null,
        ], new DateTimeImmutable('2036-08-22 10:00:00'));

        $this->assertSame('permanent', $status);
    }

    public function testRevokedStatusTakesPriorityOverPermanentStatus(): void
    {
        $status = ApplicantBlacklistService::statusOf([
            'revoked_at' => '2026-08-22 09:00:00',
            'is_permanent' => 1,
            'ends_at' => null,
        ], new DateTimeImmutable('2026-08-22 10:00:00'));

        $this->assertSame('revoked', $status);
    }
}
