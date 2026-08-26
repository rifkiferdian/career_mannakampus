<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;
use Config\Services;
use DateInterval;
use DateTimeImmutable;

class DashboardController extends BaseController
{
    /** @var array<string, list<string>> */
    private const STATUS_ALIASES = [
        'hrd_interview' => ['hrd_interview', 'interview_hr', 'interview_scheduled'],
        'user_interview' => ['user_interview', 'interview_user'],
        'accepted' => ['accepted', 'hired'],
        'administration' => ['administration', 'under_review', 'reviewed'],
    ];

    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $filters = $this->filters();
        $stages = $database->table('recruitment_stages')->where('is_active', 1)->orderBy('display_order')->get()->getResultArray();
        $applications = $this->applicationRows($filters);
        $applicationIds = array_map('intval', array_column($applications, 'id'));
        $now = time();
        $this->enrichApplications($applications, $stages, $now);

        $activeStatuses = static fn (array $row): bool => ! in_array($row['application_status'], ['accepted', 'hired', 'rejected', 'withdrawn', 'screening_failed'], true);
        $followUps = array_values(array_filter($applications, $activeStatuses));
        usort($followUps, static function (array $left, array $right): int {
            if ((bool) $left['is_overdue'] !== (bool) $right['is_overdue']) {
                return (bool) $right['is_overdue'] <=> (bool) $left['is_overdue'];
            }

            return (int) $right['days_in_stage'] <=> (int) $left['days_in_stage'];
        });

        $openVacancies = $this->openVacancies($filters, $applications);
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $canViewAllSchedules = Services::authorization()->can($userId, 'schedules.view_all');
        $reminderFilters = $filters;
        $reminderFilters['date_from'] = '';
        $reminderFilters['date_to'] = '';
        $reminderApplications = $this->applicationRows($reminderFilters);
        $this->enrichApplications($reminderApplications, $stages, $now);
        if (! Services::authorization()->can($userId, 'hrd.teams.manage')) {
            $teamIds = array_map('intval', array_column($database->table('hrd_team_users')->select('hrd_team_id')->where('user_id', $userId)->get()->getResultArray(), 'hrd_team_id'));
            $reminderApplications = array_values(array_filter($reminderApplications, static fn (array $row): bool => in_array((int) $row['assigned_hrd_team_id'], $teamIds, true)));
        }
        $scheduleAgenda = Services::authorization()->can($userId, 'schedules.view')
            ? $this->scheduleAgenda($userId, $canViewAllSchedules)
            : [];

        return view('admin/dashboard', [
            'auth' => $auth,
            'filters' => $filters,
            'departments' => $database->table('departments')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'vacancies' => $database->table('vacancies')->select('id, title')->where('deleted_at', null)->orderBy('title')->get()->getResultArray(),
            'metrics' => [
                'open_vacancies' => count($openVacancies),
                'applicants' => count(array_unique(array_map('intval', array_column($applications, 'applicant_id')))),
                'applications' => count($applications),
                'active' => count(array_filter($applications, $activeStatuses)),
                'accepted' => count(array_filter($applications, static fn (array $row): bool => in_array($row['application_status'], ['accepted', 'hired'], true))),
                'overdue' => count(array_filter($applications, static fn (array $row): bool => (bool) $row['is_overdue'])),
            ],
            'trend' => $this->trend($applications, $filters),
            'pipeline' => $this->pipeline($applications, $stages),
            'screening' => $this->screening($applications),
            'vacancyChart' => array_slice($openVacancies, 0, 7),
            'followUps' => array_slice($followUps, 0, 6),
            'activities' => $this->recentActivities($applicationIds, $stages),
            'openVacancyRows' => array_slice($openVacancies, 0, 6),
            'scheduleAgenda' => $scheduleAgenda,
            'scheduleSummary' => [
                'today' => count(array_filter($scheduleAgenda, static fn (array $row): bool => substr((string) $row['scheduled_at'], 0, 10) === date('Y-m-d'))),
                'pending' => count(array_filter($scheduleAgenda, static fn (array $row): bool => $row['status'] === 'scheduled')),
                'reschedule' => count(array_filter($scheduleAgenda, static fn (array $row): bool => $row['status'] === 'reschedule_requested')),
            ],
            'automaticReminders' => $this->automaticReminders($reminderApplications, $userId, $canViewAllSchedules, (string) ($auth['name'] ?? 'Recruiter')),
            'canViewCandidates' => Services::authorization()->can($userId, 'candidates.view'),
            'canViewReports' => Services::authorization()->can($userId, 'reports.view'),
            'canViewVacancies' => Services::authorization()->can($userId, 'vacancies.view'),
            'success' => session()->getFlashdata('auth_success'),
            'error' => session()->getFlashdata('access_error'),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function scheduleAgenda(int $userId, bool $viewAll): array
    {
        $builder = db_connect()->table('recruitment_schedules AS schedules')
            ->select('schedules.id, schedules.application_id, schedules.scheduled_at, schedules.venue, schedules.status, schedules.candidate_note, applicants.id AS applicant_id, applicants.full_name, vacancies.title AS vacancy_title, stages.name AS stage_name, pic.full_name AS pic_name, teams.name AS team_name')
            ->join('applications', 'applications.id = schedules.application_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('recruitment_stages AS stages', 'stages.id = schedules.stage_id')
            ->join('users AS pic', 'pic.id = schedules.pic_user_id')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id', 'left')
            ->whereIn('schedules.status', ['scheduled', 'confirmed', 'reschedule_requested'])
            ->groupStart()
                ->where('schedules.scheduled_at >=', date('Y-m-d 00:00:00'))
                ->orWhere('schedules.status', 'reschedule_requested')
            ->groupEnd();
        if (! $viewAll) {
            $builder->where('schedules.pic_user_id', $userId);
        }

        return $builder
            ->orderBy("CASE WHEN schedules.status = 'reschedule_requested' THEN 0 ELSE 1 END", 'ASC', false)
            ->orderBy('schedules.scheduled_at', 'ASC')->limit(8)->get()->getResultArray();
    }

    /** @param list<array<string, mixed>> $applications
     * @return list<array<string, mixed>>
     */
    private function automaticReminders(array $applications, int $userId, bool $viewAllSchedules, string $recruiterName): array
    {
        $activeStatuses = ['accepted', 'hired', 'rejected', 'withdrawn', 'screening_failed'];
        $activeApplications = array_values(array_filter($applications, static fn (array $row): bool => ! in_array((string) $row['application_status'], $activeStatuses, true)));
        $unscreened = array_values(array_filter($activeApplications, static fn (array $row): bool => ! in_array((string) $row['screening_status'], ['passed', 'failed'], true)
            && in_array((string) $row['application_status'], ['lamaran_baru', 'submitted', 'document_screening'], true)));
        $overdue = array_values(array_filter($activeApplications, static fn (array $row): bool => (bool) $row['is_overdue']));
        $applicationIds = array_map('intval', array_column($applications, 'id'));
        $schedules = [];
        if ($applicationIds !== []) {
            $scheduleBuilder = db_connect()->table('recruitment_schedules AS schedules')
                ->select('schedules.application_id, schedules.scheduled_at, schedules.confirmation_deadline_at, schedules.status, stages.code AS stage_code, stages.name AS stage_name, pic.full_name AS pic_name')
                ->join('recruitment_stages AS stages', 'stages.id = schedules.stage_id')
                ->join('users AS pic', 'pic.id = schedules.pic_user_id')
                ->whereIn('schedules.application_id', $applicationIds)
                ->where('schedules.status !=', 'cancelled');
            if (! $viewAllSchedules) {
                $scheduleBuilder->where('schedules.pic_user_id', $userId);
            }
            $schedules = $scheduleBuilder->orderBy('schedules.scheduled_at', 'ASC')->get()->getResultArray();
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $isInterview = static fn (array $row): bool => str_contains((string) $row['stage_code'], 'interview')
            || str_contains(mb_strtolower((string) $row['stage_name']), 'wawancara');
        $todayInterviews = array_values(array_filter($schedules, static fn (array $row): bool => substr((string) $row['scheduled_at'], 0, 10) === $today && $isInterview($row)));
        $pendingConfirmations = array_values(array_filter($schedules, static fn (array $row): bool => $row['status'] === 'scheduled' && (string) $row['scheduled_at'] >= $now));
        $completedUserInterviews = [];
        foreach ($schedules as $schedule) {
            $isUserInterview = (string) $schedule['stage_code'] === 'user_interview'
                || str_contains(mb_strtolower((string) $schedule['stage_name']), 'user');
            if ($isUserInterview && $schedule['status'] === 'present') {
                $completedUserInterviews[(int) $schedule['application_id']] = true;
            }
        }
        $waitingUserDecision = array_values(array_filter($activeApplications, static fn (array $row): bool => in_array((string) $row['application_status'], ['user_interview', 'interview_user'], true)
            && isset($completedUserInterviews[(int) $row['id']])));

        $oldUnscreened = count(array_filter($unscreened, static fn (array $row): bool => (int) $row['days_in_stage'] >= 2));
        $maximumOverdueDays = 0;
        foreach ($overdue as $row) {
            $maximumOverdueDays = max($maximumOverdueDays, (int) $row['days_in_stage'] - (int) $row['sla_days']);
        }
        $nearestInterview = $todayInterviews[0]['scheduled_at'] ?? null;
        $confirmationDeadlines = array_values(array_filter(array_column($pendingConfirmations, 'confirmation_deadline_at')));
        sort($confirmationDeadlines);
        $nearestConfirmationDeadline = $confirmationDeadlines[0] ?? null;
        $pic = $viewAllSchedules ? 'Semua recruiter' : $recruiterName;
        $calendarUrl = site_url('adminhrdmannakampus/kalender-rekrutmen?month=' . date('Y-m'));

        return [
            ['key' => 'screening', 'title' => 'Pelamar belum di-screening', 'count' => count($unscreened), 'description' => $oldUnscreened > 0 ? $oldUnscreened . ' pelamar sudah menunggu minimal 2 hari' : 'Tidak ada pelamar yang melewati target 2 hari', 'deadline' => 'Target maksimal 2 hari', 'pic' => $pic, 'priority' => $oldUnscreened > 0 ? 'high' : 'normal', 'href' => site_url('adminhrdmannakampus/list-pelamar')],
            ['key' => 'sla', 'title' => 'Kandidat melewati SLA', 'count' => count($overdue), 'description' => count($overdue) > 0 ? 'Kandidat terlalu lama berada pada tahap saat ini' : 'Seluruh kandidat masih dalam batas SLA', 'deadline' => $maximumOverdueDays > 0 ? 'Terlambat hingga ' . $maximumOverdueDays . ' hari' : 'Tidak ada keterlambatan', 'pic' => $pic, 'priority' => count($overdue) > 0 ? 'urgent' : 'normal', 'href' => site_url('adminhrdmannakampus/kandidat')],
            ['key' => 'interview', 'title' => 'Wawancara hari ini', 'count' => count($todayInterviews), 'description' => count($todayInterviews) > 0 ? 'Pastikan interviewer dan kandidat siap hadir' : 'Tidak ada wawancara untuk hari ini', 'deadline' => $nearestInterview ? 'Jadwal mulai ' . date('H:i', strtotime((string) $nearestInterview)) . ' WIB' : 'Hari ini', 'pic' => $pic, 'priority' => count($todayInterviews) > 0 ? 'high' : 'normal', 'href' => $calendarUrl],
            ['key' => 'confirmation', 'title' => 'Kandidat belum dikonfirmasi', 'count' => count($pendingConfirmations), 'description' => count($pendingConfirmations) > 0 ? 'Kehadiran kandidat masih menunggu konfirmasi' : 'Semua jadwal mendatang sudah dikonfirmasi', 'deadline' => $nearestConfirmationDeadline ? 'Batas ' . date('d M, H:i', strtotime((string) $nearestConfirmationDeadline)) . ' WIB' : 'Tidak ada deadline', 'pic' => $pic, 'priority' => count($pendingConfirmations) > 0 ? 'high' : 'normal', 'href' => $calendarUrl],
            ['key' => 'decision', 'title' => 'Menunggu keputusan user', 'count' => count($waitingUserDecision), 'description' => count($waitingUserDecision) > 0 ? 'Wawancara User selesai dan tahap belum diperbarui' : 'Tidak ada keputusan user yang tertunda', 'deadline' => count($waitingUserDecision) > 0 ? 'Tindak lanjuti hari ini' : 'Tidak ada deadline', 'pic' => $pic, 'priority' => count($waitingUserDecision) > 0 ? 'high' : 'normal', 'href' => site_url('adminhrdmannakampus/kandidat?status=user_interview')],
        ];
    }

    /** @param list<array<string, mixed>> $applications
     * @param list<array<string, mixed>> $stages
     */
    private function enrichApplications(array &$applications, array $stages, int $now): void
    {
        foreach ($applications as &$application) {
            $since = strtotime((string) ($application['stage_changed_at'] ?: $application['submitted_at']));
            $application['days_in_stage'] = $since === false ? 0 : max(0, (int) floor(($now - $since) / 86400));
            $application['is_overdue'] = (int) $application['sla_days'] > 0 && $application['days_in_stage'] > (int) $application['sla_days'];
            $application['status_label'] = $this->statusLabel((string) $application['application_status'], $stages);
            $application['stage_color'] = $this->stageColor((string) $application['application_status'], $stages);
        }
        unset($application);
    }

    /** @return array{period: string, date_from: string, date_to: string, department_id: int, vacancy_id: int, period_label: string} */
    private function filters(): array
    {
        $period = trim((string) $this->request->getGet('period'));
        $period = in_array($period, ['7', '30', '90', 'all', 'custom'], true) ? $period : '30';
        $today = new DateTimeImmutable('today');
        $dateFrom = '';
        $dateTo = $today->format('Y-m-d');
        $label = '30 hari terakhir';
        if (in_array($period, ['7', '30', '90'], true)) {
            $dateFrom = $today->sub(new DateInterval('P' . ((int) $period - 1) . 'D'))->format('Y-m-d');
            $label = $period . ' hari terakhir';
        } elseif ($period === 'all') {
            $dateTo = '';
            $label = 'Semua periode';
        } else {
            $dateFrom = $this->validDate((string) $this->request->getGet('date_from'));
            $dateTo = $this->validDate((string) $this->request->getGet('date_to'));
            if ($dateFrom === '' || $dateTo === '' || $dateFrom > $dateTo) {
                $period = '30';
                $dateFrom = $today->sub(new DateInterval('P29D'))->format('Y-m-d');
                $dateTo = $today->format('Y-m-d');
                $label = '30 hari terakhir';
            } else {
                $label = date('d M Y', strtotime($dateFrom)) . ' – ' . date('d M Y', strtotime($dateTo));
            }
        }

        return [
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'department_id' => max(0, (int) $this->request->getGet('department_id')),
            'vacancy_id' => max(0, (int) $this->request->getGet('vacancy_id')),
            'period_label' => $label,
        ];
    }

    /** @param array{period: string, date_from: string, date_to: string, department_id: int, vacancy_id: int, period_label: string} $filters
     * @return list<array<string, mixed>>
     */
    private function applicationRows(array $filters): array
    {
        $builder = db_connect()->table('applications AS applications')
            ->select('applications.id, applications.applicant_id, applications.vacancy_id, applications.vacancy_period_id, applications.application_number, applications.application_status, applications.screening_status, applications.submitted_at, applications.updated_at, applicants.assigned_hrd_team_id, applicants.full_name, applicants.email, vacancies.title AS vacancy_title, vacancies.department_id, departments.name AS department_name, stages.sla_days, stages.color_hex, (SELECT MAX(histories.created_at) FROM application_status_histories histories WHERE histories.application_id = applications.id) AS stage_changed_at', false)
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('recruitment_stages AS stages', 'stages.code = applications.application_status', 'left')
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
        $this->applyApplicationFilters($builder, $filters);

        return $builder->orderBy('applications.submitted_at', 'DESC')->get()->getResultArray();
    }

    /** @param array{period: string, date_from: string, date_to: string, department_id: int, vacancy_id: int, period_label: string} $filters */
    private function applyApplicationFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['date_from'] !== '') {
            $builder->where('applications.submitted_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if ($filters['date_to'] !== '') {
            $builder->where('applications.submitted_at <=', $filters['date_to'] . ' 23:59:59');
        }
        if ($filters['department_id'] > 0) {
            $builder->where('vacancies.department_id', $filters['department_id']);
        }
        if ($filters['vacancy_id'] > 0) {
            $builder->where('applications.vacancy_id', $filters['vacancy_id']);
        }
    }

    /** @param list<array<string, mixed>> $applications
     * @param array{period: string, date_from: string, date_to: string, department_id: int, vacancy_id: int, period_label: string} $filters
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function trend(array $applications, array $filters): array
    {
        $dates = array_filter(array_map(static fn (array $row): string => substr((string) $row['submitted_at'], 0, 10), $applications));
        $start = $filters['date_from'] !== '' ? new DateTimeImmutable($filters['date_from']) : new DateTimeImmutable($dates === [] ? 'today' : min($dates));
        $end = $filters['date_to'] !== '' ? new DateTimeImmutable($filters['date_to']) : new DateTimeImmutable('today');
        $days = max(1, (int) $start->diff($end)->days + 1);
        $mode = $days <= 45 ? 'day' : ($days <= 180 ? 'week' : 'month');
        $buckets = [];
        foreach ($applications as $application) {
            $time = strtotime((string) $application['submitted_at']);
            if ($time === false) {
                continue;
            }
            $key = match ($mode) {
                'week' => date('o-W', $time),
                'month' => date('Y-m', $time),
                default => date('Y-m-d', $time),
            };
            $buckets[$key] = ($buckets[$key] ?? 0) + 1;
        }

        $labels = [];
        $values = [];
        if ($mode === 'day') {
            for ($cursor = $start; $cursor <= $end; $cursor = $cursor->add(new DateInterval('P1D'))) {
                $key = $cursor->format('Y-m-d');
                $labels[] = $cursor->format('d M');
                $values[] = (int) ($buckets[$key] ?? 0);
            }
        } elseif ($mode === 'week') {
            $cursor = $start->modify('monday this week');
            while ($cursor <= $end) {
                $key = $cursor->format('o-W');
                $labels[] = $cursor->format('d M');
                $values[] = (int) ($buckets[$key] ?? 0);
                $cursor = $cursor->add(new DateInterval('P7D'));
            }
        } else {
            $cursor = $start->modify('first day of this month');
            while ($cursor <= $end) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M Y');
                $values[] = (int) ($buckets[$key] ?? 0);
                $cursor = $cursor->modify('first day of next month');
            }
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /** @param list<array<string, mixed>> $applications
     * @param list<array<string, mixed>> $stages
     * @return list<array{label: string, value: int, color: string}>
     */
    private function pipeline(array $applications, array $stages): array
    {
        $rows = [
            ['label' => 'Lamaran Baru', 'value' => 0, 'color' => '#64748B', 'codes' => ['lamaran_baru']],
            ['label' => 'Lolos screening', 'value' => 0, 'color' => '#64748B', 'codes' => ['screening_passed']],
            ['label' => 'Tidak lolos screening', 'value' => 0, 'color' => '#DC2626', 'codes' => ['screening_failed']],
        ];
        foreach ($stages as $stage) {
            $rows[] = ['label' => (string) $stage['name'], 'value' => 0, 'color' => (string) $stage['color_hex'], 'codes' => self::STATUS_ALIASES[$stage['code']] ?? [(string) $stage['code']]];
        }
        foreach ($applications as $application) {
            foreach ($rows as &$row) {
                if (in_array($application['application_status'], $row['codes'], true)) {
                    $row['value']++;
                    break;
                }
            }
            unset($row);
        }

        return array_map(static fn (array $row): array => ['label' => $row['label'], 'value' => $row['value'], 'color' => $row['color']], $rows);
    }

    /** @param list<array<string, mixed>> $applications
     * @return array{passed: int, failed: int, pending: int, total: int}
     */
    private function screening(array $applications): array
    {
        $counts = ['passed' => 0, 'failed' => 0, 'pending' => 0, 'total' => count($applications)];
        foreach ($applications as $application) {
            $status = in_array($application['screening_status'], ['passed', 'failed'], true) ? $application['screening_status'] : 'pending';
            $counts[$status]++;
        }

        return $counts;
    }

    /** @param array{period: string, date_from: string, date_to: string, department_id: int, vacancy_id: int, period_label: string} $filters
     * @param list<array<string, mixed>> $applications
     * @return list<array<string, mixed>>
     */
    private function openVacancies(array $filters, array $applications): array
    {
        $counts = array_count_values(array_map('intval', array_column($applications, 'vacancy_period_id')));
        $now = date('Y-m-d H:i:s');
        $builder = db_connect()->table('vacancies AS vacancies')
            ->select('vacancies.id, vacancies.title, periods.id AS vacancy_period_id, periods.headcount, periods.opened_at, periods.closed_at, periods.period_name, departments.name AS department_name')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('vacancy_recruitment_periods AS periods', 'periods.vacancy_id = vacancies.id')
            ->whereIn('periods.status', ['open', 'scheduled'])
            ->where('periods.deleted_at', null)
            ->where('vacancies.deleted_at', null)
            ->groupStart()->where('periods.opened_at', null)->orWhere('periods.opened_at <=', $now)->groupEnd()
            ->groupStart()->where('periods.closed_at', null)->orWhere('periods.closed_at >=', $now)->groupEnd();
        if ($filters['department_id'] > 0) {
            $builder->where('vacancies.department_id', $filters['department_id']);
        }
        if ($filters['vacancy_id'] > 0) {
            $builder->where('vacancies.id', $filters['vacancy_id']);
        }
        $rows = $builder->orderBy('periods.opened_at', 'DESC')->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['application_count'] = (int) ($counts[(int) $row['vacancy_period_id']] ?? 0);
        }
        unset($row);
        usort($rows, static fn (array $left, array $right): int => (int) $right['application_count'] <=> (int) $left['application_count']);

        return $rows;
    }

    /** @param list<int> $applicationIds
     * @param list<array<string, mixed>> $stages
     * @return list<array<string, mixed>>
     */
    private function recentActivities(array $applicationIds, array $stages): array
    {
        if ($applicationIds === []) {
            return [];
        }
        $rows = db_connect()->table('application_status_histories AS histories')
            ->select('histories.new_status, histories.notes, histories.created_at, applicants.full_name, vacancies.title AS vacancy_title, users.full_name AS changed_by_name')
            ->join('applications', 'applications.id = histories.application_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('users', 'users.id = histories.changed_by', 'left')
            ->whereIn('histories.application_id', $applicationIds)
            ->orderBy('histories.created_at', 'DESC')
            ->limit(7)
            ->get()
            ->getResultArray();
        foreach ($rows as &$row) {
            $row['status_label'] = $this->statusLabel((string) $row['new_status'], $stages);
            $row['stage_color'] = $this->stageColor((string) $row['new_status'], $stages);
        }
        unset($row);

        return $rows;
    }

    /** @param list<array<string, mixed>> $stages */
    private function statusLabel(string $status, array $stages): string
    {
        if ($status === 'lamaran_baru') {
            return 'Lamaran Baru';
        }
        if ($status === 'document_screening') {
            return 'Sedang Screening';
        }
        if ($status === 'screening_passed') {
            return 'Lolos screening';
        }
        if ($status === 'screening_failed') {
            return 'Tidak lolos screening';
        }
        foreach ($stages as $stage) {
            if ($stage['code'] === $status || in_array($status, self::STATUS_ALIASES[$stage['code']] ?? [], true)) {
                return (string) $stage['name'];
            }
        }

        return ucwords(str_replace('_', ' ', $status));
    }

    /** @param list<array<string, mixed>> $stages */
    private function stageColor(string $status, array $stages): string
    {
        foreach ($stages as $stage) {
            if ($stage['code'] === $status || in_array($status, self::STATUS_ALIASES[$stage['code']] ?? [], true)) {
                return (string) $stage['color_hex'];
            }
        }

        if ($status === 'screening_failed') {
            return '#DC2626';
        }
        if ($status === 'screening_passed') {
            return '#16A34A';
        }
        return '#64748B';
    }

    private function validDate(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date !== false && $date->format('Y-m-d') === trim($value) ? trim($value) : '';
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
