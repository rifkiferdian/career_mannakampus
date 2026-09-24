<?php

namespace Tests\Unit\Recruitment;

use App\Modules\Recruitment\Services\RecruitmentSessionService;
use App\Modules\Recruitment\Services\RecruitmentScheduleService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use InvalidArgumentException;

class RecruitmentSessionServiceTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private RecruitmentSessionService $agenda;

    protected function setUp(): void
    {
        parent::setUp();
        $this->database = Database::connect(['DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => '', 'DBDebug' => true], false);
        $tables = [
            'users' => 'id INTEGER PRIMARY KEY, full_name TEXT, is_active INTEGER, deleted_at TEXT',
            'roles' => 'id INTEGER PRIMARY KEY, code TEXT, is_active INTEGER',
            'user_roles' => 'user_id INTEGER, role_id INTEGER, expires_at TEXT',
            'permissions' => 'id INTEGER PRIMARY KEY, code TEXT, is_active INTEGER',
            'role_permissions' => 'role_id INTEGER, permission_id INTEGER',
            'hrd_team_users' => 'user_id INTEGER, hrd_team_id INTEGER',
            'recruitment_stages' => 'id INTEGER PRIMARY KEY, name TEXT, code TEXT, is_active INTEGER, is_schedulable INTEGER',
            'applicants' => 'id INTEGER PRIMARY KEY, full_name TEXT, assigned_hrd_team_id INTEGER, deleted_at TEXT',
            'applications' => 'id INTEGER PRIMARY KEY, applicant_id INTEGER, vacancy_id INTEGER, application_number TEXT, application_status TEXT, public_message TEXT, reviewed_at TEXT, reviewed_by INTEGER, updated_at TEXT, deleted_at TEXT',
            'application_status_histories' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, application_id INTEGER, status_type TEXT, previous_status TEXT, new_status TEXT, notes TEXT, changed_by INTEGER, created_at TEXT',
            'vacancies' => 'id INTEGER PRIMARY KEY, title TEXT, recruitment_process_template_id INTEGER, deleted_at TEXT',
            'recruitment_process_template_stages' => 'template_id INTEGER, stage_id INTEGER, display_order INTEGER',
            'applicant_blacklists' => 'id INTEGER PRIMARY KEY, applicant_id INTEGER, revoked_at TEXT, starts_at TEXT, is_permanent INTEGER, ends_at TEXT',
            'recruitment_sessions' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, stage_id INTEGER, starts_at TEXT, ends_at TEXT, venue TEXT, pic_user_id INTEGER, capacity INTEGER, instructions TEXT, status TEXT, created_by INTEGER, created_at TEXT, updated_at TEXT',
            'recruitment_schedules' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, application_id INTEGER, stage_id INTEGER, scheduled_at TEXT, venue TEXT, pic_user_id INTEGER, instructions TEXT, confirmation_deadline_at TEXT, status TEXT, candidate_note TEXT, created_by INTEGER, created_at TEXT, updated_at TEXT, UNIQUE(session_id, application_id)',
            'recruitment_schedule_histories' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, schedule_id INTEGER, action TEXT, notes TEXT, changed_by INTEGER, created_at TEXT',
        ];
        foreach ($tables as $name => $columns) {
            $this->database->query('CREATE TABLE ' . $name . ' (' . $columns . ')');
        }
        $this->database->table('users')->insertBatch([
            ['id' => 1, 'full_name' => 'Manager', 'is_active' => 1], ['id' => 2, 'full_name' => 'Recruiter', 'is_active' => 1], ['id' => 3, 'full_name' => 'Other team', 'is_active' => 1],
        ]);
        $this->database->table('roles')->insertBatch([['id' => 1, 'code' => 'SUPER_ADMIN', 'is_active' => 1], ['id' => 2, 'code' => 'RECRUITER', 'is_active' => 1]]);
        $this->database->table('user_roles')->insertBatch([['user_id' => 1, 'role_id' => 1], ['user_id' => 2, 'role_id' => 2], ['user_id' => 3, 'role_id' => 2]]);
        $this->database->table('permissions')->insertBatch([['id' => 1, 'code' => 'schedules.manage', 'is_active' => 1], ['id' => 2, 'code' => 'schedules.attendance', 'is_active' => 1]]);
        $this->database->table('role_permissions')->insertBatch([['role_id' => 2, 'permission_id' => 1], ['role_id' => 2, 'permission_id' => 2]]);
        $this->database->table('hrd_team_users')->insertBatch([['user_id' => 2, 'hrd_team_id' => 1], ['user_id' => 3, 'hrd_team_id' => 2]]);
        $this->database->table('recruitment_stages')->insert(['id' => 1, 'name' => 'Tes Tertulis', 'code' => 'written_test', 'is_active' => 1, 'is_schedulable' => 1]);
        $this->database->table('vacancies')->insert(['id' => 1, 'title' => 'Kasir', 'recruitment_process_template_id' => 1]);
        $this->database->table('recruitment_process_template_stages')->insert(['template_id' => 1, 'stage_id' => 1, 'display_order' => 1]);
        foreach ([1, 2, 3] as $id) {
            $this->database->table('applicants')->insert(['id' => $id, 'full_name' => 'Candidate ' . $id, 'assigned_hrd_team_id' => $id === 3 ? 2 : 1]);
            $this->database->table('applications')->insert(['id' => $id, 'applicant_id' => $id, 'vacancy_id' => 1, 'application_number' => 'APP-' . $id, 'application_status' => 'written_test']);
        }
        $this->agenda = new RecruitmentSessionService($this->database);
    }

    protected function tearDown(): void
    {
        $this->database->close();
        parent::tearDown();
    }

    private function input(array $overrides = []): array
    {
        return array_replace(['name' => 'Tes Bersama', 'stage_id' => 1, 'starts_at' => date('Y-m-d', strtotime('+3 days')) . 'T09:00',
            'ends_at' => date('Y-m-d', strtotime('+3 days')) . 'T11:00', 'venue' => 'Ruang Tes', 'pic_user_id' => 2, 'capacity' => '10', 'status' => 'scheduled'], $overrides);
    }

    private function deadline(): string
    {
        return date('Y-m-d', strtotime('+2 days')) . 'T10:00';
    }

    private function rejects(callable $action, string $message): void
    {
        try {
            $action();
            self::fail('Expected validation rejection: ' . $message);
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
    }

    public function testBulkAddSharesPicAndRetainsPerCandidateSchedules(): void
    {
        $id = $this->agenda->save($this->input(), 2);
        self::assertSame(2, $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2));
        $rows = $this->database->table('recruitment_schedules')->get()->getResultArray();
        self::assertCount(2, $rows);
        self::assertSame($rows[0]['scheduled_at'], $rows[1]['scheduled_at']);
        self::assertSame($id, (int) $rows[0]['session_id']);
        self::assertSame(2, $this->database->table('recruitment_schedule_histories')->countAllResults());
        $this->rejects(fn () => $this->agenda->addParticipants($id, [1], $this->deadline(), 2), 'sudah terdaftar');
        self::assertSame(2, $this->database->table('recruitment_schedules')->countAllResults());
    }

    public function testAddParticipantsAdvancesOnlyFromPreviousTemplateStage(): void
    {
        $this->database->table('recruitment_stages')->insert(['id' => 2, 'name' => 'Wawancara HRD', 'code' => 'hrd_interview', 'is_active' => 1, 'is_schedulable' => 1]);
        $this->database->table('recruitment_process_template_stages')->insert(['template_id' => 1, 'stage_id' => 2, 'display_order' => 2]);
        $this->database->table('recruitment_schedules')->insert([
            'application_id' => 1, 'stage_id' => 1, 'pic_user_id' => 2, 'status' => 'present',
            'scheduled_at' => date('Y-m-d', strtotime('-1 day')) . ' 09:00:00',
        ]);
        $this->database->table('recruitment_schedules')->insert([
            'application_id' => 2, 'stage_id' => 1, 'pic_user_id' => 2, 'status' => 'scheduled',
            'scheduled_at' => date('Y-m-d', strtotime('-2 days')) . ' 09:00:00',
        ]);
        $staleScheduleId = (int) $this->database->insertID();
        $id = $this->agenda->save($this->input(['name' => 'Wawancara Bersama', 'stage_id' => 2]), 2);

        self::assertSame(2, $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2));
        self::assertSame('hrd_interview', $this->database->table('applications')->where('id', 1)->get()->getRowArray()['application_status']);
        self::assertSame('hrd_interview', $this->database->table('applications')->where('id', 2)->get()->getRowArray()['application_status']);
        self::assertSame(2, $this->database->table('application_status_histories')->where('previous_status', 'written_test')->where('new_status', 'hrd_interview')->countAllResults());
        self::assertSame(1, $this->database->table('recruitment_schedules')->where('session_id', $id)->where('application_id', 1)->countAllResults());
        self::assertSame('cancelled', $this->database->table('recruitment_schedules')->where('id', $staleScheduleId)->get()->getRowArray()['status']);
    }

    public function testQuotaAndCrossTeamSelectionRejectWholeBatch(): void
    {
        $id = $this->agenda->save($this->input(['capacity' => '1']), 2);
        $this->rejects(fn () => $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2), 'kuota');
        $this->rejects(fn () => $this->agenda->addParticipants($id, [1, 3], $this->deadline(), 2), 'di luar tim');
        self::assertSame(0, $this->database->table('recruitment_schedules')->countAllResults());
        $this->rejects(fn () => $this->agenda->find($id, 3), 'akses');
        $this->rejects(fn () => $this->agenda->save($this->input(), 3, $id), 'akses');
    }

    public function testStageAndBlacklistAreValidatedAtSubmission(): void
    {
        $id = $this->agenda->save($this->input(), 2);
        $this->database->table('applications')->where('id', 2)->update(['application_status' => 'accepted']);
        $this->rejects(fn () => $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2), 'berbeda tahap');
        $this->database->table('applicant_blacklists')->insert(['applicant_id' => 1, 'starts_at' => '2020-01-01 00:00:00', 'is_permanent' => 1]);
        $this->rejects(fn () => $this->agenda->addParticipants($id, [1], $this->deadline(), 2), 'blacklist');
        self::assertSame(0, $this->database->table('recruitment_schedules')->countAllResults());
    }

    public function testSessionEditSynchronizesMembersAndResetsConfirmation(): void
    {
        $id = $this->agenda->save($this->input(), 2);
        $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2);
        $this->database->table('recruitment_schedules')->update(['status' => 'confirmed']);
        $this->agenda->save($this->input(['venue' => 'Ruang Baru', 'confirmation_deadline_at' => $this->deadline()]), 2, $id);
        foreach ($this->database->table('recruitment_schedules')->get()->getResultArray() as $row) {
            self::assertSame('Ruang Baru', $row['venue']);
            self::assertSame('scheduled', $row['status']);
        }
        $this->rejects(fn () => $this->agenda->save($this->input(['status' => 'draft']), 2, $id), 'tetap terjadwal');
    }

    public function testAttendanceAndCompletionRequireExecutionAndCancellationCascades(): void
    {
        $id = $this->agenda->save($this->input(), 2);
        $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2);
        $row = $this->database->table('recruitment_schedules')->get()->getRowArray();
        $this->rejects(fn () => $this->agenda->participantStatus($id, (int) $row['id'], 'present', 2), 'setelah jadwal');
        $this->rejects(fn () => $this->agenda->changeStatus($id, 'completed', 2), 'seluruh kehadiran');
        $this->agenda->changeStatus($id, 'cancelled', 2);
        self::assertSame(2, $this->database->table('recruitment_schedules')->where('status', 'cancelled')->countAllResults());
        $this->rejects(fn () => $this->agenda->addParticipants($id, [3], $this->deadline(), 1), 'dibatalkan');
    }

    public function testReusesIndividualScheduleAndKeepsHistory(): void
    {
        $this->database->table('recruitment_schedules')->insert(['application_id' => 1, 'stage_id' => 1, 'pic_user_id' => 2, 'status' => 'confirmed', 'scheduled_at' => date('Y-m-d', strtotime('+4 days')) . ' 09:00:00']);
        $scheduleId = (int) $this->database->insertID();
        $id = $this->agenda->save($this->input(), 2);
        $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2);
        self::assertSame(2, $this->database->table('recruitment_schedules')->countAllResults());
        self::assertSame($id, (int) $this->database->table('recruitment_schedules')->where('id', $scheduleId)->get()->getRowArray()['session_id']);
        self::assertSame('rescheduled', $this->database->table('recruitment_schedule_histories')->where('schedule_id', $scheduleId)->get()->getRowArray()['action']);
    }

    public function testConflictingParticipantRollsBackEarlierParticipants(): void
    {
        $this->database->table('applications')->insert(['id' => 4, 'applicant_id' => 2, 'vacancy_id' => 1, 'application_status' => 'written_test']);
        $this->database->table('recruitment_schedules')->insert(['application_id' => 4, 'stage_id' => 1, 'pic_user_id' => 3, 'status' => 'scheduled', 'scheduled_at' => date('Y-m-d', strtotime('+3 days')) . ' 09:00:00']);
        $id = $this->agenda->save($this->input(), 2);
        $this->rejects(fn () => $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2), 'jam yang sama');
        self::assertSame(0, $this->database->table('recruitment_schedules')->where('session_id', $id)->countAllResults());
        self::assertSame(0, $this->database->table('recruitment_schedule_histories')->countAllResults());
    }

    public function testRejectsPicOverlapButAllowsAdjacentSessions(): void
    {
        $this->agenda->save($this->input(), 2);
        $this->rejects(fn () => $this->agenda->save($this->input(), 2), 'bertabrakan');
        $id = $this->agenda->save($this->input(['starts_at' => date('Y-m-d', strtotime('+3 days')) . 'T11:00', 'ends_at' => '']), 2);
        self::assertGreaterThan(0, $id);
    }

    public function testRejectsInvalidDateAndUnauthorisedUser(): void
    {
        $this->rejects(fn () => RecruitmentSessionService::parseDate('2027-02-30T09:00'), 'tidak valid');
        $this->rejects(fn () => $this->agenda->save($this->input(), 999), 'hak akses');
    }

    public function testAttendanceCompletionAndParticipantOwnership(): void
    {
        $id = $this->agenda->save($this->input(), 2);
        $this->agenda->addParticipants($id, [1, 2], $this->deadline(), 2);
        $other = $this->agenda->save($this->input(['pic_user_id' => 3]), 1);
        $this->agenda->addParticipants($other, [3], $this->deadline(), 1);
        $foreignRow = $this->database->table('recruitment_schedules')->where('session_id', $other)->get()->getRowArray();
        $this->rejects(fn () => $this->agenda->participantStatus($id, (int) $foreignRow['id'], 'cancelled', 2), 'tidak aktif');
        $this->database->table('recruitment_sessions')->where('id', $id)->update(['starts_at' => '2020-01-01 09:00:00']);
        $this->database->table('recruitment_schedules')->where('session_id', $id)->update(['scheduled_at' => '2020-01-01 09:00:00']);
        foreach ($this->database->table('recruitment_schedules')->where('session_id', $id)->get()->getResultArray() as $row) {
            $this->agenda->participantStatus($id, (int) $row['id'], 'present', 2);
        }
        $this->agenda->changeStatus($id, 'completed', 2);
        self::assertSame('completed', $this->agenda->find($id, 2)['status']);
        self::assertSame(2, $this->database->table('recruitment_schedules')->where('session_id', $id)->where('status', 'present')->countAllResults());
    }

    public function testDraftCannotAcceptParticipantsAndPermissionIsChecked(): void
    {
        $id = $this->agenda->save($this->input(['status' => 'draft']), 2);
        $this->rejects(fn () => $this->agenda->addParticipants($id, [1], $this->deadline(), 2), 'Terjadwal');
        $this->database->table('role_permissions')->where('permission_id', 1)->delete();
        $this->rejects(fn () => $this->agenda->changeStatus($id, 'cancelled', 2), 'hak akses');
    }

    public function testDuplicateApplicantAcrossVacanciesRollsBackBatch(): void
    {
        $this->database->table('applications')->insert(['id' => 4, 'applicant_id' => 1, 'vacancy_id' => 1, 'application_status' => 'written_test']);
        $id = $this->agenda->save($this->input(), 2);
        $this->rejects(fn () => $this->agenda->addParticipants($id, [1, 4], $this->deadline(), 2), 'jam yang sama');
        self::assertSame(0, $this->database->table('recruitment_schedules')->countAllResults());
    }

    public function testIndividualSchedulingRespectsEmptySessionReservation(): void
    {
        $this->agenda->save($this->input(), 2);
        $service = new RecruitmentScheduleService($this->database);
        $this->rejects(fn () => $service->validateInput([
            'scheduled_at' => date('Y-m-d', strtotime('+3 days')) . 'T10:00',
            'confirmation_deadline_at' => $this->deadline(), 'venue' => 'Other room', 'pic_user_id' => 2,
        ]), 'Agenda Seleksi');
    }
}
