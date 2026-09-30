<?php

namespace Tests\Unit\Recruitment;

use App\Modules\Recruitment\Services\ApplicantFollowupReportService as Report;
use CodeIgniter\Test\CIUnitTestCase;

class ApplicantFollowupReportTest extends CIUnitTestCase
{
    private function stages(): array
    {
        return ['document_screening' => ['id' => 1, 'name' => 'Screening berkas', 'is_schedulable' => 0],
            'written_test' => ['id' => 2, 'name' => 'Tes tertulis', 'is_schedulable' => 1],
            'hrd_interview' => ['id' => 3, 'name' => 'Wawancara HRD', 'is_schedulable' => 1]];
    }

    private function schedule(int $stage, string $status): array
    {
        return ['stage_id' => $stage, 'status' => $status, 'scheduled_at' => '2026-09-20 10:00:00', 'created_at' => '2026-09-18 10:00:00', 'pic_name' => 'HRD'];
    }

    private function history(): array
    {
        return ['status_type' => 'application', 'previous_status' => 'written_test', 'new_status' => 'hrd_interview', 'created_at' => '2026-09-21 09:00:00'];
    }

    public function testScreeningRequiresNoScreeningDecisionAndEarlyStage(): void
    {
        $app = ['application_status' => 'document_screening', 'screening_status' => 'pending', 'submitted_at' => '2026-09-10 08:00:00'];
        $result = Report::classify($app, [], [], $this->stages(), 'screening', '2026-09-30 10:00:00');
        self::assertSame('screening', $result['condition']);
        self::assertSame($app['submitted_at'], $result['reference_at']);
        foreach (['passed', 'failed'] as $status) {
            self::assertNull(Report::classify(array_replace($app, ['screening_status' => $status]), [], [], $this->stages(), 'screening', '2026-09-30 10:00:00'));
        }
        foreach (['written_test', 'screening_passed', 'rejected', 'accepted'] as $status) {
            self::assertNull(Report::classify(array_replace($app, ['application_status' => $status]), [], [], $this->stages(), 'screening', '2026-09-30 10:00:00'));
        }
    }

    public function testDecisionWaitUsesAttendedScheduleNotApplicationOrHistoryDate(): void
    {
        $app = ['application_status' => 'written_test'];
        $result = Report::classify($app, [$this->schedule(2, 'present')], [], $this->stages(), 'followup', '2026-09-30 10:00:00');
        self::assertSame('decision', $result['condition']);
        self::assertSame('2026-09-20 10:00:00', $result['reference_at']);
        foreach (['scheduled', 'confirmed', 'reschedule_requested', 'absent', 'cancelled'] as $status) {
            self::assertNull(Report::classify($app, [$this->schedule(2, $status)], [], $this->stages(), 'followup', '2026-09-30 10:00:00'));
        }
        foreach (['document_screening', 'rejected', 'accepted', 'hired', 'withdrawn', 'screening_failed'] as $status) {
            self::assertNull(Report::classify(['application_status' => $status], [$this->schedule(2, 'present')], [], $this->stages(), 'followup', '2026-09-30 10:00:00'));
        }
    }

    public function testScheduleWaitNeedsAttendedPreviousStageAndMatchingTransition(): void
    {
        $app = ['application_status' => 'interview_hr'];
        $schedules = [$this->schedule(2, 'present')];
        $histories = [$this->history()];
        $result = Report::classify($app, $schedules, $histories, $this->stages(), 'followup', '2026-09-30 10:00:00');
        self::assertSame('schedule', $result['condition']);
        self::assertSame('2026-09-21 09:00:00', $result['reference_at']);
        self::assertSame('Tes tertulis', $result['last_stage']);
        self::assertSame('Wawancara HRD', $result['next_stage']);
        self::assertNull(Report::classify($app, $schedules, [], $this->stages(), 'followup', '2026-09-30 10:00:00'));
        self::assertNull(Report::classify($app, [$this->schedule(2, 'absent')], $histories, $this->stages(), 'followup', '2026-09-30 10:00:00'));
        self::assertNull(Report::classify($app, [$this->schedule(3, 'scheduled'), ...$schedules], $histories, $this->stages(), 'followup', '2026-09-30 10:00:00'));
        $screeningTransition = array_replace($this->history(), ['previous_status' => 'document_screening']);
        self::assertNull(Report::classify($app, $schedules, [$screeningTransition], $this->stages(), 'followup', '2026-09-30 10:00:00'));
    }

    public function testLatestAttemptAndNewStageCycleDoNotReuseOldAttendance(): void
    {
        $app = ['application_status' => 'written_test'];
        self::assertNull(Report::classify($app, [$this->schedule(2, 'absent'), $this->schedule(2, 'present')], [], $this->stages(), 'followup', '2026-09-30 10:00:00'));
        $transition = array_replace($this->history(), ['previous_status' => 'document_screening', 'new_status' => 'written_test']);
        self::assertNull(Report::classify($app, [$this->schedule(2, 'present')], [$transition], $this->stages(), 'followup', '2026-09-30 10:00:00'));
    }

    public function testFiltersRejectArraysAndInvalidDatesAndNormalizeRange(): void
    {
        $filters = Report::filters(['keyword' => ['bad'], 'date_from' => '2026-02-30', 'condition' => 'invalid', 'min_days' => '-1']);
        self::assertSame('', $filters['keyword']);
        self::assertSame('', $filters['date_from']);
        self::assertSame('', $filters['condition']);
        self::assertSame(0, $filters['min_days']);
        $filters = Report::filters(['date_from' => '2026-09-30', 'date_to' => '2026-09-01']);
        self::assertSame('2026-09-01', $filters['date_from']);
        self::assertSame('2026-09-30', $filters['date_to']);
    }

    public function testReportViewRendersBothModesAndEscapesApplicantNames(): void
    {
        helper('url');
        $row = ['full_name' => '<script>alert(1)</script>', 'application_number' => 'APP-1', 'vacancy_title' => 'Kasir',
            'team_name' => 'HRD', 'last_stage' => 'Tes tertulis', 'next_stage' => 'Putuskan lolos / gugur', 'condition' => 'decision',
            'reference_at' => '2026-09-20 10:00:00', 'reference_label' => 'Pelaksanaan (tercatat hadir)', 'wait_days' => 10,
            'applicant_id' => 1, 'assigned_hrd_team_id' => 1, 'pic_name' => 'HRD'];
        foreach (['screening', 'followup'] as $mode) {
            $html = view('admin/reports/applicant_followup', [
                'mode' => $mode, 'title' => 'Report', 'base' => site_url('adminhrdmannakampus/report/tindak-lanjut'),
                'summary' => ['total' => 1, 'unassigned' => 0, 'decision' => 1, 'schedule' => 0, 'longest' => 10],
                'filters' => Report::filters([]), 'teams' => [], 'vacancies' => [], 'stages' => [], 'conditions' => Report::CONDITIONS,
                'rows' => [$row], 'total' => 1, 'page' => 1,
            ]);
            self::assertStringContainsString('Unduh Excel', $html);
            self::assertStringContainsString('10 hari', $html);
            self::assertStringContainsString('&lt;script&gt;', $html);
            self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        }
    }
}
