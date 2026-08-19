<?php

namespace Tests\Unit\Recruitment;

use App\Modules\Recruitment\Presenters\ApplicationStatusPresenter;
use CodeIgniter\Test\CIUnitTestCase;

class ApplicationStatusPresenterTest extends CIUnitTestCase
{
    public function testItMasksIdentityAndDoesNotExposeInternalScreeningData(): void
    {
        $presenter = new ApplicationStatusPresenter();
        $result = $presenter->present([
            'batch_number'       => 'SAMPLE-BATCH-001',
            'submitted_at'       => '2026-07-25 07:00:00',
            'applicant_snapshot' => json_encode([
                'identity' => ['full_name' => 'Budi Santoso'],
                'contact'  => ['email' => 'budi.santoso@example.test'],
            ], JSON_THROW_ON_ERROR),
        ], [[
            'application_number' => 'SAMPLE-APP-001',
            'preference_order'   => 1,
            'vacancy_title'      => 'Programmer',
            'department_name'    => 'Information Technology',
            'application_status' => 'screening_passed',
            'public_message'     => 'Lolos screening awal.',
            'updated_at'         => '2026-07-25 07:01:00',
            'screening_score'    => 100,
            'screening_notes'    => 'Catatan internal tidak boleh tampil.',
        ]]);

        $this->assertSame('Budi S******', $result['applicant_name']);
        $this->assertSame('bu**********@example.test', $result['applicant_email']);
        $this->assertSame('Lolos Screening', $result['applications'][0]['status_label']);
        $this->assertArrayNotHasKey('screening_score', $result['applications'][0]);
        $this->assertArrayNotHasKey('screening_notes', $result['applications'][0]);
    }

    public function testItUsesSafeFallbackForUnknownApplicationStatus(): void
    {
        $presenter = new ApplicationStatusPresenter();
        $result = $presenter->present([
            'batch_number'       => 'MKB-001',
            'submitted_at'       => '2026-07-25 07:00:00',
            'applicant_snapshot' => '{}',
        ], [[
            'application_number' => 'MK-001',
            'preference_order'   => 1,
            'vacancy_title'      => 'Posisi Contoh',
            'department_name'    => '',
            'application_status' => 'internal_custom_status',
            'public_message'     => '',
            'updated_at'         => '2026-07-25 07:00:00',
        ]]);

        $this->assertSame('Lamaran diterima', $result['applications'][0]['status_label']);
        $this->assertSame('neutral', $result['applications'][0]['status_tone']);
    }

    public function testItPresentsNewApplicationAsWaitingForManualScreening(): void
    {
        $presenter = new ApplicationStatusPresenter();
        $result = $presenter->present([
            'batch_number' => 'MKB-002',
            'submitted_at' => '2026-08-19 10:00:00',
            'applicant_snapshot' => '{}',
        ], [[
            'application_number' => 'MK-002',
            'preference_order' => 1,
            'vacancy_title' => 'Programmer',
            'department_name' => 'Information Technology',
            'application_status' => 'lamaran_baru',
            'public_message' => 'Lamaran Anda telah diterima.',
            'updated_at' => '2026-08-19 10:00:00',
        ]]);

        $this->assertSame('Lamaran Baru', $result['applications'][0]['status_label']);
        $this->assertSame('neutral', $result['applications'][0]['status_tone']);
    }
}
