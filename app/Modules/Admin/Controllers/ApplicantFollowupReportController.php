<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Services\ExcelWorkbookBuilder;
use App\Modules\Recruitment\Services\ApplicantFollowupReportService;
use CodeIgniter\HTTP\DownloadResponse;

class ApplicantFollowupReportController extends BaseController
{
    public function screening(): string
    {
        return $this->page('screening');
    }

    public function followup(): string
    {
        return $this->page('followup');
    }

    private function data(string $mode): array
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        $auth = session()->get('hrd_auth');
        $filters = ApplicantFollowupReportService::filters((array) $this->request->getGet());
        if ($mode === 'screening') {
            $filters['condition'] = '';
            $filters['stage_id'] = 0;
            $filters['team_id'] = 0;
            $filters['unassigned'] = '';
            $filters['min_days'] = 0;
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        } else {
            $filters['unassigned'] = '';
            $filters['condition'] = '';
            $filters['team_id'] = 0;
            $filters['stage_id'] = 0;
            $filters['min_days'] = 0;
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        }
        return (new ApplicantFollowupReportService(db_connect()))->report((int) ($auth['user_id'] ?? 0), $mode, $filters)
            + ['filters' => $filters, 'auth' => $auth, 'mode' => $mode,
                'title' => $mode === 'screening' ? 'Pelamar Belum Screening' : 'Tindak Lanjut Seleksi',
                'activeMenu' => 'report-' . $mode,
                'base' => site_url('adminhrdmannakampus/report/' . ($mode === 'screening' ? 'belum-screening' : 'tindak-lanjut')),
                'conditions' => ApplicantFollowupReportService::CONDITIONS];
    }

    private function page(string $mode): string
    {
        $data = $this->data($mode);
        $total = count($data['rows']);
        $pageInput = $this->request->getGet('page');
        $page = min(max(1, is_scalar($pageInput) ? (int) $pageInput : 1), max(1, (int) ceil($total / 50)));
        $data['rows'] = array_slice($data['rows'], ($page - 1) * 50, 50);
        return view('admin/agenda/layout', $data + compact('total', 'page') + [
            'contentView' => 'admin/reports/applicant_followup', 'success' => null, 'error' => null,
        ]);
    }

    public function exportScreening(): DownloadResponse
    {
        return $this->export('screening');
    }

    public function exportFollowup(): DownloadResponse
    {
        return $this->export('followup');
    }

    private function export(string $mode): DownloadResponse
    {
        $data = $this->data($mode);
        $rows = array_map(static fn (array $row): array => [
            $row['full_name'], $row['application_number'], $row['vacancy_title'], $row['team_name'] ?: 'Belum dibagikan',
            $row['last_stage'], $data['conditions'][$row['condition']], $row['next_stage'],
            $row['reference_at'], $row['reference_label'], $row['wait_days'], $row['pic_name'] ?: '-',
        ], $data['rows']);
        $workbook = (new ExcelWorkbookBuilder())->build($data['title'],
            ['Pelamar', 'Nomor lamaran', 'Posisi', 'Divisi', 'Tahap terakhir', 'Kondisi', 'Tindak lanjut', 'Tanggal acuan (WIB)', 'Acuan', 'Menunggu (hari)', 'PIC tahap terakhir'], $rows,
            'Diekspor ' . date('d/m/Y H:i') . ' WIB. Satu baris per lamaran; sesuai filter dan hak akses pengguna.');
        return $this->response->download('report-' . $mode . '-' . date('Ymd-His') . '.xlsx', $workbook, true);
    }
}
