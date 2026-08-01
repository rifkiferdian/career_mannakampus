<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Services\ExcelWorkbookBuilder;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\DownloadResponse;
use DateTimeImmutable;

class ApplicantReportController extends BaseController
{
    /** @var array<string, string> */
    private const STATUS_LABELS = [
        'submitted' => 'Lamaran diterima',
        'screening_passed' => 'Lolos screening',
        'screening_failed' => 'Tidak lolos screening',
        'administration' => 'Administrasi',
        'under_review' => 'Sedang ditinjau',
        'test_scheduled' => 'Jadwal tes',
        'assessment' => 'Tahap asesmen',
        'hrd_interview' => 'Interview HRD',
        'user_interview' => 'Interview User',
        'psychotest' => 'Psikotes',
        'medical_checkup' => 'Medical Check-up',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'withdrawn' => 'Dibatalkan',
    ];

    /** @var array<string, list<string>> */
    private const STATUS_ALIASES = [
        'under_review' => ['under_review', 'reviewed'],
        'hrd_interview' => ['hrd_interview', 'interview_hr', 'interview_scheduled'],
        'user_interview' => ['user_interview', 'interview_user'],
        'accepted' => ['accepted', 'hired'],
    ];

    public function index(): string
    {
        $this->disableClientCaching();
        $filters = $this->filters();
        $applications = $this->reportRows($filters);
        $applicantIds = array_unique(array_map('intval', array_column($applications, 'applicant_id')));

        return view('admin/applicant_report', [
            'auth' => session()->get('hrd_auth'),
            'applications' => $applications,
            'filters' => $filters,
            'vacancies' => db_connect()->table('vacancies')->select('id, title')->where('deleted_at', null)->orderBy('title')->get()->getResultArray(),
            'departments' => db_connect()->table('departments')->select('id, name')->orderBy('name')->get()->getResultArray(),
            'statusLabels' => self::STATUS_LABELS,
            'summary' => [
                'applicants' => count($applicantIds),
                'applications' => count($applications),
                'passed' => count(array_filter($applications, static fn (array $row): bool => $row['screening_status'] === 'passed')),
                'failed' => count(array_filter($applications, static fn (array $row): bool => $row['screening_status'] === 'failed')),
            ],
        ]);
    }

    public function export(): DownloadResponse
    {
        $this->disableClientCaching();
        $rows = $this->reportRows($this->filters());
        $excelRows = array_map(function (array $row): array {
            return [
                $row['application_number'],
                $row['full_name'],
                $row['email'],
                $row['phone'],
                $row['vacancy_title'],
                $row['department_name'],
                $this->formatDate((string) $row['submitted_at']),
                $this->screeningLabel((string) $row['screening_status']),
                $row['screening_score'] === null ? '-' : number_format((float) $row['screening_score'], 2, ',', '.'),
                $this->statusLabel((string) $row['application_status']),
            ];
        }, $rows);
        $workbook = (new ExcelWorkbookBuilder())->build(
            'Laporan Pelamar Manna Kampus',
            ['No. Lamaran', 'Nama Pelamar', 'Email', 'WhatsApp', 'Posisi', 'Departemen', 'Tanggal Daftar', 'Status Screening', 'Nilai Screening', 'Status Lamaran'],
            $excelRows,
        );

        return $this->response->download('laporan-pelamar-' . date('Ymd-His') . '.xlsx', $workbook, true);
    }

    /** @return array{keyword: string, vacancy_id: int, department_id: int, status: string, date_from: string, date_to: string} */
    private function filters(): array
    {
        $status = trim((string) $this->request->getGet('status'));
        if ($status !== '' && ! array_key_exists($status, self::STATUS_LABELS)) {
            $status = '';
        }

        return [
            'keyword' => mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100),
            'vacancy_id' => max(0, (int) $this->request->getGet('vacancy_id')),
            'department_id' => max(0, (int) $this->request->getGet('department_id')),
            'status' => $status,
            'date_from' => $this->validDate((string) $this->request->getGet('date_from')),
            'date_to' => $this->validDate((string) $this->request->getGet('date_to')),
        ];
    }

    /** @param array{keyword: string, vacancy_id: int, department_id: int, status: string, date_from: string, date_to: string} $filters
     * @return list<array<string, mixed>>
     */
    private function reportRows(array $filters): array
    {
        $builder = db_connect()->table('applications AS applications')
            ->select('applications.application_number, applications.applicant_id, applications.screening_status, applications.screening_score, applications.application_status, applications.submitted_at, applicants.full_name, applicants.email, applicants.phone, vacancies.title AS vacancy_title, departments.name AS department_name')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
        $this->applyFilters($builder, $filters);

        $rows = $builder->orderBy('applications.submitted_at', 'DESC')->orderBy('applications.id', 'DESC')->get()->getResultArray();

        return array_map(function (array $row): array {
            $row['status_label'] = $this->statusLabel((string) $row['application_status']);

            return $row;
        }, $rows);
    }

    /** @param array{keyword: string, vacancy_id: int, department_id: int, status: string, date_from: string, date_to: string} $filters */
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

    private function screeningLabel(string $status): string
    {
        return match ($status) {
            'passed' => 'Lolos',
            'failed' => 'Tidak lolos',
            default => 'Belum dinilai',
        };
    }

    private function statusLabel(string $status): string
    {
        if (isset(self::STATUS_LABELS[$status])) {
            return self::STATUS_LABELS[$status];
        }
        foreach (self::STATUS_ALIASES as $canonical => $aliases) {
            if (in_array($status, $aliases, true)) {
                return self::STATUS_LABELS[$canonical];
            }
        }

        return ucwords(str_replace('_', ' ', $status));
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
