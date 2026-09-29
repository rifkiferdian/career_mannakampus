<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Services\ExcelWorkbookBuilder;
use App\Modules\Recruitment\Services\AgendaRecapService;
use App\Modules\Recruitment\Services\RecruitmentSessionService;
use CodeIgniter\HTTP\DownloadResponse;

class AgendaRecapController extends BaseController
{
    public function index(): string
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $service = new AgendaRecapService(db_connect());
        $filters = AgendaRecapService::filters((array) $this->request->getGet());
        $summary = $service->summary($userId, $filters);
        $total = (int) $summary['agendas'];
        $page = min(max(1, (int) $this->request->getGet('page')), max(1, (int) ceil($total / 50)));
        $rows = $service->rows($userId, $filters, 50, ($page - 1) * 50);
        $agenda = new RecruitmentSessionService(db_connect());
        return view('admin/agenda/layout', compact('auth', 'filters', 'summary', 'total', 'page', 'rows') + [
            'title' => 'Rekap Agenda', 'activeMenu' => 'agenda-recap', 'contentView' => 'admin/agenda/recap',
            'success' => null, 'error' => null, 'statuses' => RecruitmentSessionService::STATUSES,
            'pics' => $agenda->visibleSessions($userId)->distinct()->select('users.id, users.full_name')
                ->join('users', 'users.id = sessions.pic_user_id')->orderBy('users.full_name')->get()->getResultArray(),
            'stages' => db_connect()->table('recruitment_stages')->where('is_schedulable', 1)->orderBy('display_order')->get()->getResultArray(),
        ]);
    }

    public function export(): DownloadResponse
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        $userId = (int) (session()->get('hrd_auth')['user_id'] ?? 0);
        $filters = AgendaRecapService::filters((array) $this->request->getGet());
        $rows = (new AgendaRecapService(db_connect()))->rows($userId, $filters);
        $values = array_map(static fn (array $row): array => [
            $row['name'], date('d/m/Y H:i', strtotime($row['starts_at'])), $row['stage_name'], $row['pic_name'],
            RecruitmentSessionService::STATUSES[$row['status']] ?? $row['status'],
            (int) $row['participants'], (int) $row['present'], (int) $row['absent'], (int) $row['pending'], (int) $row['cancelled'],
            (int) $row['present'] + (int) $row['absent'] > 0 ? round(100 * $row['present'] / ($row['present'] + $row['absent']), 1) . '%' : '-',
        ], $rows);
        $workbook = (new ExcelWorkbookBuilder())->build('Rekap Agenda',
            ['Agenda', 'Mulai (WIB)', 'Tahap', 'PIC', 'Status', 'Terdaftar', 'Hadir', 'Tidak hadir', 'Belum dicatat / akan datang', 'Dibatalkan', 'Kehadiran'], $values);
        return $this->response->download('rekap-agenda-' . $filters['date_from'] . '-' . $filters['date_to'] . '.xlsx', $workbook, true);
    }
}
