<?php

namespace Tests\Unit\Recruitment;

use App\Modules\Recruitment\Services\RecruitmentScheduleService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class RecruitmentScheduleServiceTest extends CIUnitTestCase
{
    public function testItRejectsPastSchedule(): void
    {
        $service = new RecruitmentScheduleService($this->createMock(BaseConnection::class));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('masa mendatang');
        $service->validateInput([
            'scheduled_at' => '2020-01-01T10:00',
            'confirmation_deadline_at' => '2019-12-31T10:00',
            'venue' => 'Ruang HRD',
            'pic_user_id' => 1,
        ]);
    }

    public function testItRejectsDeadlineAfterSchedule(): void
    {
        $service = new RecruitmentScheduleService($this->createMock(BaseConnection::class));
        $tomorrow = new \DateTimeImmutable('+1 day');
        $afterTomorrow = new \DateTimeImmutable('+2 days');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sebelum jadwal');
        $service->validateInput([
            'scheduled_at' => $tomorrow->format('Y-m-d\TH:i'),
            'confirmation_deadline_at' => $afterTomorrow->format('Y-m-d\TH:i'),
            'venue' => 'Ruang HRD',
            'pic_user_id' => 1,
        ]);
    }
}
