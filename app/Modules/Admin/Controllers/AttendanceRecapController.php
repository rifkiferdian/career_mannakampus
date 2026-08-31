<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Services\ExcelWorkbookBuilder;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\DownloadResponse;
use Config\Services;
use DateTimeImmutable;

class AttendanceRecapController extends BaseController
{
    private const PER_PAGE = 50;

    private const STATUS_LABELS = [
        'scheduled' => 'Menunggu konfirmasi',
        'confirmed' => 'Terkonfirmasi',
        'reschedule_requested' => 'Minta jadwal ulang',
        'present' => 'Hadir',
        'absent' => 'Tidak hadir',
        'cancelled' => 'Dibatalkan',
    ];

    public function index(): string
    {
        $this->disableClientCaching();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $canViewAll = Services::authorization()->can($userId, 'schedules.view_all');
        $filters = $this->filters($canViewAll);
        $summary = $this->summary($filters, $userId, $canViewAll);
        $totalPages = max(1, (int) ceil($summary['total'] / self::PER_PAGE));
        $page = min(max(1, (int) $this->request->getGet('page')), $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;

        return view('admin/attendance_recap', [
            'auth' => $auth,
            'rows' => $this->rows($filters, $userId, $canViewAll, self::PER_PAGE, $offset),
            'filters' => $filters,
            'summary' => $summary,
            'pagination' => ['page' => $page, 'per_page' => self::PER_PAGE, 'total' => $summary['total'], 'total_pages' => $totalPages, 'offset' => $offset],
            'stages' => db_connect()->table('recruitment_stages')->select('id, name')->where('is_schedulable', 1)->orderBy('display_order')->get()->getResultArray(),
            'vacancies' => db_connect()->table('vacancies')->select('id, title')->where('deleted_at', null)->orderBy('title')->get()->getResultArray(),
            'teams' => $canViewAll ? db_connect()->table('hrd_teams')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray() : [],
            'canViewAll' => $canViewAll,
            'canRecordAttendance' => Services::authorization()->can($userId, 'schedules.attendance'),
            'canViewApplicant' => Services::authorization()->can($userId, 'candidates.view'),
            'statusLabels' => self::STATUS_LABELS,
            'success' => session()->getFlashdata('candidate_success'),
            'error' => session()->getFlashdata('candidate_error'),
        ]);
    }

    public function export(): DownloadResponse
    {
        $this->disableClientCaching();
        $userId = (int) (session()->get('hrd_auth')['user_id'] ?? 0);
        $canViewAll = Services::authorization()->can($userId, 'schedules.view_all');
        $rows = $this->rows($this->filters($canViewAll), $userId, $canViewAll);
        $excelRows = array_map(static fn (array $row): array => [
            date('d/m/Y', strtotime((string) $row['scheduled_at'])),
            date('H:i', strtotime((string) $row['scheduled_at'])),
            $row['full_name'],
            $row['application_number'],
            $row['stage_name'],
            $row['vacancy_title'],
            $row['hrd_team_name'] ?: '-',
            $row['pic_name'],
            $row['venue'],
            $row['display_status'],
        ], $rows);
        $workbook = (new ExcelWorkbookBuilder())->build(
            'Rekap Kehadiran Seleksi',
            ['Tanggal', 'Jam', 'Pelamar', 'Nomor Lamaran', 'Tahapan', 'Posisi', 'Divisi HRD', 'PIC', 'Lokasi / Link', 'Kehadiran'],
            $excelRows,
        );

        return $this->response->download('rekap-kehadiran-' . date('Ymd-His') . '.xlsx', $workbook, true);
    }

    /** @return array{keyword:string,stage_id:int,vacancy_id:int,team_id:int,status:string,date_from:string,date_to:string} */
    private function filters(bool $canViewAll): array
    {
        $status = trim((string) $this->request->getGet('status'));
        $validStatuses = array_merge(array_keys(self::STATUS_LABELS), ['unrecorded', 'upcoming']);
        if (! in_array($status, $validStatuses, true)) {
            $status = '';
        }
        $dateFrom = $this->validDate((string) $this->request->getGet('date_from'));
        $dateTo = $this->validDate((string) $this->request->getGet('date_to'));
        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-t');
        }
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'keyword' => mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100),
            'stage_id' => max(0, (int) $this->request->getGet('stage_id')),
            'vacancy_id' => max(0, (int) $this->request->getGet('vacancy_id')),
            'team_id' => $canViewAll ? max(0, (int) $this->request->getGet('team_id')) : 0,
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /** @param array{keyword:string,stage_id:int,vacancy_id:int,team_id:int,status:string,date_from:string,date_to:string} $filters
     * @return list<array<string,mixed>> */
    private function rows(array $filters, int $userId, bool $canViewAll, ?int $limit = null, int $offset = 0): array
    {
        $builder = $this->baseBuilder();
        $this->applyFilters($builder, $filters, $userId, $canViewAll);
        $builder->orderBy('schedules.scheduled_at', 'DESC')->orderBy('schedules.id', 'DESC');
        if ($limit !== null) {
            $builder->limit($limit, max(0, $offset));
        }

        return array_map(function (array $row): array {
            $row['display_status'] = $this->displayStatus((string) $row['status'], (string) $row['scheduled_at']);
            $row['display_status_code'] = $this->displayStatusCode((string) $row['status'], (string) $row['scheduled_at']);

            return $row;
        }, $builder->get()->getResultArray());
    }

    /** @param array{keyword:string,stage_id:int,vacancy_id:int,team_id:int,status:string,date_from:string,date_to:string} $filters
     * @return array{total:int,present:int,absent:int,unrecorded:int,upcoming:int} */
    private function summary(array $filters, int $userId, bool $canViewAll): array
    {
        $builder = $this->baseBuilder(false)->select("COUNT(DISTINCT schedules.id) AS total, COUNT(DISTINCT CASE WHEN schedules.status = 'present' THEN schedules.id END) AS present, COUNT(DISTINCT CASE WHEN schedules.status = 'absent' THEN schedules.id END) AS absent, COUNT(DISTINCT CASE WHEN schedules.status IN ('scheduled','confirmed') AND schedules.scheduled_at < NOW() THEN schedules.id END) AS unrecorded, COUNT(DISTINCT CASE WHEN schedules.status IN ('scheduled','confirmed') AND schedules.scheduled_at >= NOW() THEN schedules.id END) AS upcoming", false);
        $this->applyFilters($builder, $filters, $userId, $canViewAll);
        $row = $builder->get()->getRowArray() ?? [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'present' => (int) ($row['present'] ?? 0),
            'absent' => (int) ($row['absent'] ?? 0),
            'unrecorded' => (int) ($row['unrecorded'] ?? 0),
            'upcoming' => (int) ($row['upcoming'] ?? 0),
        ];
    }

    private function baseBuilder(bool $withSelect = true): BaseBuilder
    {
        $builder = db_connect()->table('recruitment_schedules AS schedules')
            ->join('applications', 'applications.id = schedules.application_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('recruitment_stages AS stages', 'stages.id = schedules.stage_id')
            ->join('users AS pic', 'pic.id = schedules.pic_user_id')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id', 'left')
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
        if ($withSelect) {
            $builder->select('schedules.id, schedules.scheduled_at, schedules.status, schedules.venue, schedules.candidate_note, applications.application_number, applicants.id AS applicant_id, applicants.full_name, applicants.email, applicants.assigned_hrd_team_id, vacancies.title AS vacancy_title, stages.name AS stage_name, pic.full_name AS pic_name, teams.name AS hrd_team_name');
        }

        return $builder;
    }

    /** @param array{keyword:string,stage_id:int,vacancy_id:int,team_id:int,status:string,date_from:string,date_to:string} $filters */
    private function applyFilters(BaseBuilder $builder, array $filters, int $userId, bool $canViewAll): void
    {
        if (! $canViewAll) {
            $builder->where('schedules.pic_user_id', $userId);
        }
        if ($filters['keyword'] !== '') {
            $builder->groupStart()->like('applicants.full_name', $filters['keyword'])->orLike('applicants.email', $filters['keyword'])->orLike('applications.application_number', $filters['keyword'])->groupEnd();
        }
        if ($filters['stage_id'] > 0) {
            $builder->where('schedules.stage_id', $filters['stage_id']);
        }
        if ($filters['vacancy_id'] > 0) {
            $builder->where('applications.vacancy_id', $filters['vacancy_id']);
        }
        if ($filters['team_id'] > 0) {
            $builder->where('applicants.assigned_hrd_team_id', $filters['team_id']);
        }
        if ($filters['date_from'] !== '') {
            $builder->where('schedules.scheduled_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if ($filters['date_to'] !== '') {
            $builder->where('schedules.scheduled_at <=', $filters['date_to'] . ' 23:59:59');
        }
        if ($filters['status'] === 'unrecorded') {
            $builder->whereIn('schedules.status', ['scheduled', 'confirmed'])->where('schedules.scheduled_at <', date('Y-m-d H:i:s'));
        } elseif ($filters['status'] === 'upcoming') {
            $builder->whereIn('schedules.status', ['scheduled', 'confirmed'])->where('schedules.scheduled_at >=', date('Y-m-d H:i:s'));
        } elseif ($filters['status'] !== '') {
            $builder->where('schedules.status', $filters['status']);
        }
    }

    private function displayStatusCode(string $status, string $scheduledAt): string
    {
        if (in_array($status, ['scheduled', 'confirmed'], true)) {
            return strtotime($scheduledAt) < time() ? 'unrecorded' : 'upcoming';
        }

        return $status;
    }

    private function displayStatus(string $status, string $scheduledAt): string
    {
        $code = $this->displayStatusCode($status, $scheduledAt);
        if ($code === 'unrecorded') {
            return 'Belum dicatat';
        }
        if ($code === 'upcoming') {
            return $status === 'confirmed' ? 'Akan datang - terkonfirmasi' : 'Akan datang - menunggu konfirmasi';
        }

        return self::STATUS_LABELS[$status] ?? ucfirst($status);
    }

    private function validDate(string $value): string
    {
        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
