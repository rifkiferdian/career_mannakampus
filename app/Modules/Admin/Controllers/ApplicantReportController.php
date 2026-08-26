<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Services\ExcelWorkbookBuilder;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\DownloadResponse;
use Config\Services;
use DateTimeImmutable;

class ApplicantReportController extends BaseController
{
    private const PER_PAGE = 50;

    /** Status sistem ini hanya untuk label data, bukan pilihan filter tahapan. */
    private const SYSTEM_STATUS_LABELS = [
        'lamaran_baru' => 'Lamaran Baru',
        'submitted' => 'Lamaran diterima',
        'screening_passed' => 'Lolos screening',
        'screening_failed' => 'Tidak lolos screening',
        'withdrawn' => 'Dibatalkan',
    ];

    /** @var array<string, list<string>> */
    private const STATUS_ALIASES = [
        'administration' => ['administration', 'under_review', 'reviewed'],
        'hrd_interview' => ['hrd_interview', 'interview_hr', 'interview_scheduled'],
        'user_interview' => ['user_interview', 'interview_user'],
        'accepted' => ['accepted', 'hired'],
    ];

    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $stages = $database->table('recruitment_stages')->orderBy('display_order')->get()->getResultArray();
        $statusLabels = $this->statusLabels($stages);
        $filters = $this->filters(array_keys($statusLabels));
        $summary = $this->reportSummary($filters);
        $totalPages = max(1, (int) ceil($summary['applicants'] / self::PER_PAGE));
        $page = min(max(1, (int) $this->request->getGet('page')), $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;
        $applications = $this->reportRows($filters, self::SYSTEM_STATUS_LABELS + $statusLabels, self::PER_PAGE, $offset);
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $currentTeam = $this->currentTeam($userId);

        return view('admin/applicant_report', [
            'auth' => $auth,
            'applications' => $applications,
            'pagination' => ['page' => $page, 'per_page' => self::PER_PAGE, 'total' => $summary['applicants'], 'total_pages' => $totalPages, 'offset' => $offset],
            'filters' => $filters,
            'vacancies' => $database->table('vacancies')->select('id, title')->where('deleted_at', null)->orderBy('title')->get()->getResultArray(),
            'periods' => $database->table('vacancy_recruitment_periods AS periods')->select('periods.id, periods.period_name, vacancies.title AS vacancy_title')->join('vacancies', 'vacancies.id = periods.vacancy_id')->where('periods.deleted_at', null)->orderBy('periods.opened_at', 'DESC')->get()->getResultArray(),
            'departments' => $database->table('departments')->select('id, name')->orderBy('name')->get()->getResultArray(),
            'statusLabels' => $statusLabels,
            'canViewCandidate' => Services::authorization()->can($userId, 'candidates.view'),
            'canAssignPermission' => Services::authorization()->can($userId, 'applicants.assign'),
            'canAssign' => Services::authorization()->can($userId, 'applicants.assign') && $currentTeam !== null,
            'currentTeam' => $currentTeam,
            'success' => session()->getFlashdata('applicant_pool_success'),
            'error' => session()->getFlashdata('applicant_pool_error'),
            'summary' => $summary,
        ]);
    }

    public function export(): DownloadResponse
    {
        $this->disableClientCaching();
        $stages = db_connect()->table('recruitment_stages')->orderBy('display_order')->get()->getResultArray();
        $statusLabels = $this->statusLabels($stages);
        $rows = $this->reportRows($this->filters(array_keys($statusLabels)), self::SYSTEM_STATUS_LABELS + $statusLabels);
        $excelRows = array_map(function (array $row): array {
            return [
                $row['full_name'],
                $row['email'],
                $row['phone'],
                $row['application_count'],
                $row['position_names'],
                $row['period_names'],
                $row['department_names'],
                $this->formatDate((string) $row['submitted_at']),
                $row['hrd_team_name'] ?: 'Belum dipilih',
                $row['assigned_by_name'] ?: '-',
                $row['status_label'],
            ];
        }, $rows);
        $workbook = (new ExcelWorkbookBuilder())->build(
            'List Pelamar Manna Kampus',
            ['Nama Pelamar', 'Email', 'WhatsApp', 'Jumlah Lamaran', 'Posisi Dilamar', 'Sesi Lowongan', 'Departemen', 'Tanggal Daftar Terakhir', 'Divisi HRD', 'Dipilih Oleh', 'Status Lamaran'],
            $excelRows,
        );

        return $this->response->download('list-pelamar-' . date('Ymd-His') . '.xlsx', $workbook, true);
    }

    public function assign(int $applicantId): \CodeIgniter\HTTP\RedirectResponse
    {
        $database = db_connect();
        $userId = (int) (session()->get('hrd_auth')['user_id'] ?? 0);
        $team = $this->currentTeam($userId);
        if ($team === null) {
            return $this->poolError('Akun Anda belum ditempatkan pada divisi HRD aktif. Hubungi pengelola Tim HRD.');
        }
        if (Services::applicantBlacklist()->isActive($applicantId)) {
            return $this->poolError('Pelamar berada dalam blacklist aktif dan tidak dapat dipilih oleh divisi.');
        }

        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('applicants')
            ->where('id', $applicantId)
            ->where('deleted_at', null)
            ->where('assigned_hrd_team_id', null)
            ->update([
                'assigned_hrd_team_id' => (int) $team['id'],
                'assigned_by_user_id' => $userId,
                'assigned_at' => $now,
                'updated_at' => $now,
            ]);
        $claimed = $database->affectedRows() === 1;
        if ($claimed) {
            $database->table('applicant_assignment_histories')->insert([
                'applicant_id' => $applicantId,
                'from_hrd_team_id' => null,
                'to_hrd_team_id' => (int) $team['id'],
                'action' => 'assigned',
                'notes' => 'Pelamar dipilih oleh ' . $team['name'] . '.',
                'changed_by' => $userId,
                'created_at' => $now,
            ]);
        }
        $database->transComplete();
        if (! $claimed || ! $database->transStatus()) {
            return $this->poolError('Pelamar sudah dipilih divisi lain atau data tidak lagi tersedia.');
        }

        return redirect()->to(site_url('adminhrdmannakampus/list-pelamar'))->with('applicant_pool_success', 'Pelamar berhasil dipilih untuk ' . $team['name'] . '.');
    }

    /**
     * @param list<string> $validStatuses
     * @return array{keyword: string, vacancy_id: int, vacancy_period_id: int, department_id: int, status: string, date_from: string, date_to: string}
     */
    private function filters(array $validStatuses): array
    {
        $status = trim((string) $this->request->getGet('status'));
        if ($status !== '' && ! in_array($status, $validStatuses, true)) {
            $status = '';
        }

        return [
            'keyword' => mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100),
            'vacancy_id' => max(0, (int) $this->request->getGet('vacancy_id')),
            'vacancy_period_id' => max(0, (int) $this->request->getGet('vacancy_period_id')),
            'department_id' => max(0, (int) $this->request->getGet('department_id')),
            'status' => $status,
            'date_from' => $this->validDate((string) $this->request->getGet('date_from')),
            'date_to' => $this->validDate((string) $this->request->getGet('date_to')),
        ];
    }

    /**
     * @param array{keyword: string, vacancy_id: int, vacancy_period_id: int, department_id: int, status: string, date_from: string, date_to: string} $filters
     * @param array<string, string> $statusLabels
     * @return list<array<string, mixed>>
     */
    private function reportRows(array $filters, array $statusLabels, ?int $limit = null, int $offset = 0): array
    {
        $builder = db_connect()->table('applications AS applications')
            ->select("applicants.id AS applicant_id, applicants.assigned_hrd_team_id, applicants.assigned_at, applicants.full_name, applicants.email, applicants.phone, applicants.birth_date, applicants.address, teams.name AS hrd_team_name, assigned_user.full_name AS assigned_by_name, active_blacklist.id AS active_blacklist_id, COUNT(DISTINCT applications.id) AS application_count, MAX(applications.submitted_at) AS submitted_at, GROUP_CONCAT(DISTINCT vacancies.title ORDER BY applications.preference_order SEPARATOR '||') AS position_names, GROUP_CONCAT(DISTINCT periods.period_name ORDER BY applications.preference_order SEPARATOR '||') AS period_names, GROUP_CONCAT(DISTINCT departments.name ORDER BY departments.name SEPARATOR '||') AS department_names, GROUP_CONCAT(CONCAT(vacancies.title, '::', applications.application_status) ORDER BY applications.preference_order, applications.id SEPARATOR '||') AS position_statuses", false)
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('vacancy_recruitment_periods AS periods', 'periods.id = applications.vacancy_period_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id', 'left')
            ->join('users AS assigned_user', 'assigned_user.id = applicants.assigned_by_user_id', 'left')
            ->join('applicant_blacklists AS active_blacklist', 'active_blacklist.applicant_id = applicants.id AND active_blacklist.revoked_at IS NULL AND active_blacklist.starts_at <= NOW() AND (active_blacklist.is_permanent = 1 OR active_blacklist.ends_at >= NOW())', 'left', false)
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
        $this->applyFilters($builder, $filters);

        $builder
            ->groupBy(['applicants.id', 'applicants.assigned_hrd_team_id', 'applicants.assigned_at', 'applicants.full_name', 'applicants.email', 'applicants.phone', 'applicants.birth_date', 'applicants.address', 'teams.name', 'assigned_user.full_name', 'active_blacklist.id'])
            ->orderBy('submitted_at', 'DESC')
            ->orderBy('applicants.id', 'DESC');
        if ($limit !== null) {
            $builder->limit($limit, max(0, $offset));
        }
        $rows = $builder->get()->getResultArray();

        return array_map(function (array $row) use ($statusLabels): array {
            $positionStatuses = [];
            foreach (array_filter(explode('||', (string) $row['position_statuses'])) as $positionStatus) {
                [$position, $status] = array_pad(explode('::', $positionStatus, 2), 2, '');
                $positionStatuses[] = [
                    'position' => $position,
                    'status' => $this->statusLabel($status, $statusLabels),
                    'code' => $status,
                ];
            }
            $row['position_statuses'] = $positionStatuses;
            $row['status_label'] = implode('; ', array_map(static fn (array $item): string => $item['position'] . ': ' . $item['status'], $positionStatuses));
            $row['position_names'] = str_replace('||', ', ', (string) $row['position_names']);
            $row['period_names'] = str_replace('||', ', ', (string) $row['period_names']);
            $row['department_names'] = str_replace('||', ', ', (string) $row['department_names']);
            $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($row['birth_date'] ?? ''));
            $today = new DateTimeImmutable('today');
            $row['age'] = $birthDate !== false && $birthDate <= $today ? $birthDate->diff($today)->y : null;

            return $row;
        }, $rows);
    }

    /**
     * @param array{keyword: string, vacancy_id: int, vacancy_period_id: int, department_id: int, status: string, date_from: string, date_to: string} $filters
     * @return array{applicants: int, applications: int, unassigned: int, assigned: int}
     */
    private function reportSummary(array $filters): array
    {
        $builder = db_connect()->table('applications AS applications')
            ->select("COUNT(DISTINCT applicants.id) AS applicants, COUNT(DISTINCT applications.id) AS applications, COUNT(DISTINCT CASE WHEN applicants.assigned_hrd_team_id IS NULL AND active_blacklist.id IS NULL THEN applicants.id END) AS unassigned, COUNT(DISTINCT CASE WHEN applicants.assigned_hrd_team_id IS NOT NULL THEN applicants.id END) AS assigned", false)
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('vacancy_recruitment_periods AS periods', 'periods.id = applications.vacancy_period_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('applicant_blacklists AS active_blacklist', 'active_blacklist.applicant_id = applicants.id AND active_blacklist.revoked_at IS NULL AND active_blacklist.starts_at <= NOW() AND (active_blacklist.is_permanent = 1 OR active_blacklist.ends_at >= NOW())', 'left', false)
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
        $this->applyFilters($builder, $filters);
        $row = $builder->get()->getRowArray() ?? [];

        return [
            'applicants' => (int) ($row['applicants'] ?? 0),
            'applications' => (int) ($row['applications'] ?? 0),
            'unassigned' => (int) ($row['unassigned'] ?? 0),
            'assigned' => (int) ($row['assigned'] ?? 0),
        ];
    }

    /** @param array{keyword: string, vacancy_id: int, vacancy_period_id: int, department_id: int, status: string, date_from: string, date_to: string} $filters */
    private function applyFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['keyword'] !== '') {
            $builder->groupStart()
                ->like('applicants.full_name', $filters['keyword'])
                ->orLike('applicants.email', $filters['keyword'])
                ->orLike('applicants.phone', $filters['keyword'])
                ->orLike('applications.application_number', $filters['keyword'])
                ->groupEnd();
        }
        if ($filters['vacancy_id'] > 0) {
            $builder->where('applications.vacancy_id', $filters['vacancy_id']);
        }
        if ($filters['vacancy_period_id'] > 0) {
            $builder->where('applications.vacancy_period_id', $filters['vacancy_period_id']);
        }
        if ($filters['department_id'] > 0) {
            $builder->where('vacancies.department_id', $filters['department_id']);
        }
        if ($filters['status'] !== '') {
            $builder->whereIn('applications.application_status', self::STATUS_ALIASES[$filters['status']] ?? [$filters['status']]);
        }
        if ($filters['date_from'] !== '') {
            $builder->where('applications.submitted_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if ($filters['date_to'] !== '') {
            $builder->where('applications.submitted_at <=', $filters['date_to'] . ' 23:59:59');
        }
    }

    private function validDate(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date !== false && $date->format('Y-m-d') === trim($value) ? trim($value) : '';
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);

        return $timestamp === false ? '-' : date('d/m/Y H:i', $timestamp);
    }

    /** @param array<string, string> $statusLabels */
    private function statusLabel(string $status, array $statusLabels): string
    {
        if (isset($statusLabels[$status])) {
            return $statusLabels[$status];
        }
        foreach (self::STATUS_ALIASES as $canonical => $aliases) {
            if (in_array($status, $aliases, true)) {
                return $statusLabels[$canonical] ?? ucwords(str_replace('_', ' ', $canonical));
            }
        }

        return ucwords(str_replace('_', ' ', $status));
    }

    /**
     * @param list<array<string, mixed>> $stages
     * @return array<string, string>
     */
    private function statusLabels(array $stages): array
    {
        $labels = [];
        foreach ($stages as $stage) {
            $code = trim((string) ($stage['code'] ?? ''));
            if ($code !== '') {
                $labels[$code] = (string) ($stage['name'] ?? $code);
            }
        }

        return $labels;
    }

    /** @return array<string, mixed>|null */
    private function currentTeam(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        return db_connect()->table('hrd_team_users AS team_users')
            ->select('teams.id, teams.name, teams.code')
            ->join('hrd_teams AS teams', 'teams.id = team_users.hrd_team_id')
            ->where('team_users.user_id', $userId)
            ->where('teams.is_active', 1)
            ->get()->getRowArray() ?: null;
    }

    private function poolError(string $message): \CodeIgniter\HTTP\RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/list-pelamar'))->with('applicant_pool_error', $message);
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
