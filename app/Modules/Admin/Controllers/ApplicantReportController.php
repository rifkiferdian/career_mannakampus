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
    /** Status sistem ini hanya untuk label data, bukan pilihan filter tahapan. */
    private const SYSTEM_STATUS_LABELS = [
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
        $applications = $this->reportRows($filters, self::SYSTEM_STATUS_LABELS + $statusLabels);
        $applicantIds = array_unique(array_map('intval', array_column($applications, 'applicant_id')));
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $currentTeam = $this->currentTeam($userId);

        return view('admin/applicant_report', [
            'auth' => $auth,
            'applications' => $applications,
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
            'summary' => [
                'applicants' => count($applicantIds),
                'applications' => count($applications),
                'unassigned' => count(array_filter($applications, static fn (array $row): bool => empty($row['assigned_hrd_team_id']))),
                'assigned' => count(array_filter($applications, static fn (array $row): bool => ! empty($row['assigned_hrd_team_id']))),
            ],
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
                $row['application_number'],
                $row['full_name'],
                $row['email'],
                $row['phone'],
                $row['vacancy_title'],
                $row['period_name'],
                $row['department_name'],
                $this->formatDate((string) $row['submitted_at']),
                $row['hrd_team_name'] ?: 'Belum dipilih',
                $row['assigned_by_name'] ?: '-',
                $row['status_label'],
            ];
        }, $rows);
        $workbook = (new ExcelWorkbookBuilder())->build(
            'List Pelamar Manna Kampus',
            ['No. Lamaran', 'Nama Pelamar', 'Email', 'WhatsApp', 'Posisi', 'Sesi Lowongan', 'Departemen', 'Tanggal Daftar', 'Divisi HRD', 'Dipilih Oleh', 'Status Lamaran'],
            $excelRows,
        );

        return $this->response->download('list-pelamar-' . date('Ymd-His') . '.xlsx', $workbook, true);
    }

    public function assign(int $applicationId): \CodeIgniter\HTTP\RedirectResponse
    {
        $database = db_connect();
        $userId = (int) (session()->get('hrd_auth')['user_id'] ?? 0);
        $team = $this->currentTeam($userId);
        if ($team === null) {
            return $this->poolError('Akun Anda belum ditempatkan pada divisi HRD aktif. Hubungi pengelola Tim HRD.');
        }

        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('applications')
            ->where('id', $applicationId)
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
            $database->table('application_assignment_histories')->insert([
                'application_id' => $applicationId,
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
    private function reportRows(array $filters, array $statusLabels): array
    {
        $builder = db_connect()->table('applications AS applications')
            ->select('applications.id, applications.application_number, applications.applicant_id, applications.application_status, applications.assigned_hrd_team_id, applications.assigned_at, applications.submitted_at, applicants.full_name, applicants.email, applicants.phone, vacancies.title AS vacancy_title, periods.period_name, departments.name AS department_name, teams.name AS hrd_team_name, assigned_user.full_name AS assigned_by_name')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('vacancy_recruitment_periods AS periods', 'periods.id = applications.vacancy_period_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('hrd_teams AS teams', 'teams.id = applications.assigned_hrd_team_id', 'left')
            ->join('users AS assigned_user', 'assigned_user.id = applications.assigned_by_user_id', 'left')
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
        $this->applyFilters($builder, $filters);

        $rows = $builder->orderBy('applications.submitted_at', 'DESC')->orderBy('applications.id', 'DESC')->get()->getResultArray();

        return array_map(function (array $row) use ($statusLabels): array {
            $row['status_label'] = $this->statusLabel((string) $row['application_status'], $statusLabels);

            return $row;
        }, $rows);
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
