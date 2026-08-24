<?php

namespace Tests\Unit\Recruitment;

use App\Modules\Recruitment\Services\HistoricalBlacklistService;
use CodeIgniter\Test\CIUnitTestCase;

final class HistoricalBlacklistServiceTest extends CIUnitTestCase
{
    public function testEmailIsTrimmedAndLowercased(): void
    {
        $this->assertSame('pelamar@example.com', HistoricalBlacklistService::normalizeEmail(' Pelamar@Example.COM '));
    }

    public function testLocalPhoneIsNormalizedToIndonesiaCountryCode(): void
    {
        $this->assertSame('6281234567890', HistoricalBlacklistService::normalizePhone('0812-3456-7890'));
        $this->assertSame('6281234567890', HistoricalBlacklistService::normalizePhone('812 3456 7890'));
    }

    public function testInternationalPhoneRemainsNormalized(): void
    {
        $this->assertSame('6281234567890', HistoricalBlacklistService::normalizePhone('+62 812-3456-7890'));
    }
}
