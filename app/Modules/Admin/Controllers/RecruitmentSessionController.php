<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Support\AgendaPeriod;
use App\Modules\Recruitment\Services\RecruitmentSessionService;
use CodeIgniter\HTTP\RedirectResponse;
use InvalidArgumentException;

class RecruitmentSessionController extends BaseController
{
    private function agenda(): RecruitmentSessionService
    {
        return new RecruitmentSessionService(db_connect());
    }

    private function userId(): int
    {
        return (int) (session()->get('hrd_auth')['user_id'] ?? 0);
    }

    public function index(): string
    {
        $service = $this->agenda();
        $period = new AgendaPeriod(trim((string) ($this->request->getGet('date') ?? date('Y-m-d'))), (string) $this->request->getGet('view'));
        $filters = ['date' => $period->selectedDate(), 'view' => $period->view(), 'stage_id' => max(0, (int) $this->request->getGet('stage_id')),
            'pic_user_id' => max(0, (int) $this->request->getGet('pic_user_id')), 'status' => (string) $this->request->getGet('status')];
        $query = $service->visibleSessions($this->userId())
            ->select('sessions.*, stages.name AS stage_name, pic.full_name AS pic_name')
            ->join('recruitment_stages AS stages', 'stages.id = sessions.stage_id')
            ->join('users AS pic', 'pic.id = sessions.pic_user_id')
            ->where('sessions.starts_at >=', $period->from())->where('sessions.starts_at <=', $period->until());
        foreach (['stage_id', 'pic_user_id'] as $field) {
            if ($filters[$field] > 0) {
                $query->where('sessions.' . $field, $filters[$field]);
            }
        }
        if (isset(RecruitmentSessionService::STATUSES[$filters['status']])) {
            $query->where('sessions.status', $filters['status']);
        }
        $total = (clone $query)->countAllResults();
        $page = $period->view() === 'day' ? min(max(1, (int) $this->request->getGet('page')), max(1, (int) ceil($total / 50))) : 1;
        $query->orderBy('sessions.starts_at')->orderBy('sessions.id');
        $rows = $period->view() === 'day'
            ? $query->get(50, ($page - 1) * 50)->getResultArray()
            : $query->get()->getResultArray();
        $counts = [];
        if ($rows !== []) {
            $counts = array_column(db_connect()->table('recruitment_schedules')->select('session_id, COUNT(*) AS total', false)
                ->whereIn('session_id', array_column($rows, 'id'))->where('status !=', 'cancelled')->groupBy('session_id')->get()->getResultArray(), 'total', 'session_id');
        }

        $rowsByDate = [];
        foreach ($rows as $row) {
            $rowsByDate[substr($row['starts_at'], 0, 10)][] = $row;
        }

        return $this->render('index', compact('rows', 'rowsByDate', 'counts', 'filters', 'page', 'total', 'period') + ['title' => 'Agenda Seleksi', 'pics' => $service->pics($this->userId())]);
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string|RedirectResponse
    {
        try {
            return $this->form($this->agenda()->find($id, $this->userId()));
        } catch (InvalidArgumentException $exception) {
            return $this->failure($exception);
        }
    }

    private function form(?array $agenda): string
    {
        return $this->render('form', ['title' => $agenda === null ? 'Buat Agenda Seleksi' : 'Ubah Agenda Seleksi', 'agenda' => $agenda,
            'pics' => $this->agenda()->pics($this->userId())]);
    }

    public function store(): RedirectResponse
    {
        return $this->save(null);
    }

    public function update(int $id): RedirectResponse
    {
        return $this->save($id);
    }

    private function save(?int $id): RedirectResponse
    {
        try {
            $id = $this->agenda()->save((array) $this->request->getPost(), $this->userId(), $id);

            return $this->success($id, 'Agenda berhasil disimpan.');
        } catch (InvalidArgumentException $exception) {
            return redirect()->to(site_url('adminhrdmannakampus/agenda/' . ($id === null ? 'baru' : $id . '/edit')))->withInput()->with('agenda_error', $exception->getMessage());
        }
    }

    public function show(int $id): string|RedirectResponse
    {
        try {
            $service = $this->agenda();
            $agenda = $service->find($id, $this->userId());
            $query = $service->participants($id);
            $total = (clone $query)->countAllResults();
            $page = min(max(1, (int) $this->request->getGet('page')), max(1, (int) ceil($total / 50)));
            $rows = $query->orderBy('schedules.scheduled_at')->orderBy('applicants.full_name')->get(50, ($page - 1) * 50)->getResultArray();
            $summary = array_column(db_connect()->table('recruitment_schedules')->select('status, COUNT(*) AS total', false)->where('session_id', $id)->groupBy('status')->get()->getResultArray(), 'total', 'status');
            $deadline = db_connect()->table('recruitment_schedules')
                ->selectMin('confirmation_deadline_at', 'minimum')
                ->selectMax('confirmation_deadline_at', 'maximum')
                ->where('session_id', $id)->where('status !=', 'cancelled')->get()->getRowArray();

            return $this->render('show', compact('agenda', 'rows', 'summary', 'deadline', 'page', 'total') + ['title' => $agenda['name']]);
        } catch (InvalidArgumentException $exception) {
            return $this->failure($exception);
        }
    }

    public function candidates(int $id): string|RedirectResponse
    {
        try {
            $service = $this->agenda();
            $agenda = $service->find($id, $this->userId());
            $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100);
            $vacancyId = max(0, (int) $this->request->getGet('vacancy_id'));
            $query = $service->eligible($agenda, $this->userId());
            if ($keyword !== '') {
                $query->groupStart()->like('applicants.full_name', $keyword)->orLike('applications.application_number', $keyword)->groupEnd();
            }
            if ($vacancyId > 0) {
                $query->where('applications.vacancy_id', $vacancyId);
            }
            $total = (clone $query)->countAllResults();
            $page = min(max(1, (int) $this->request->getGet('page')), max(1, (int) ceil($total / 50)));
            $rows = $query->orderBy('applicants.full_name')->orderBy('applications.id')->get(50, ($page - 1) * 50)->getResultArray();
            $vacancies = db_connect()->table('vacancies')->select('id, title')->where('deleted_at', null)->orderBy('title')->get()->getResultArray();

            return $this->render('candidates', compact('agenda', 'keyword', 'vacancyId', 'rows', 'vacancies', 'total', 'page') + ['title' => 'Tambah Peserta']);
        } catch (InvalidArgumentException $exception) {
            return $this->failure($exception);
        }
    }

    public function addParticipants(int $id): RedirectResponse
    {
        try {
            $ids = $this->request->getPost('application_ids');
            $count = $this->agenda()->addParticipants($id, is_array($ids) ? $ids : [], (string) $this->request->getPost('confirmation_deadline_at'), $this->userId());

            return $this->success($id, $count . ' peserta berhasil ditambahkan ke agenda.');
        } catch (InvalidArgumentException $exception) {
            return redirect()->to(site_url('adminhrdmannakampus/agenda/' . $id . '/peserta'))->withInput()->with('agenda_error', $exception->getMessage());
        }
    }

    public function status(int $id): RedirectResponse
    {
        try {
            $this->agenda()->changeStatus($id, (string) $this->request->getPost('status'), $this->userId());

            return $this->success($id, 'Status agenda berhasil diperbarui.');
        } catch (InvalidArgumentException $exception) {
            return $this->failure($exception, $id);
        }
    }

    public function attendance(int $id, int $scheduleId): RedirectResponse
    {
        return $this->participantAction($id, $scheduleId, (string) $this->request->getPost('status'));
    }

    public function cancelParticipant(int $id, int $scheduleId): RedirectResponse
    {
        return $this->participantAction($id, $scheduleId, 'cancelled');
    }

    private function participantAction(int $id, int $scheduleId, string $status): RedirectResponse
    {
        try {
            $this->agenda()->participantStatus($id, $scheduleId, $status, $this->userId());

            return $this->success($id, 'Status peserta berhasil diperbarui.');
        } catch (InvalidArgumentException $exception) {
            return $this->failure($exception, $id);
        }
    }

    private function render(string $page, array $data): string
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $service = $this->agenda();

        return view('admin/agenda/layout', $data + ['contentView' => 'admin/agenda/' . $page,
            'auth' => session()->get('hrd_auth'), 'statuses' => RecruitmentSessionService::STATUSES,
            'participantStatuses' => RecruitmentSessionService::PARTICIPANT_STATUSES,
            'canManage' => $service->can($this->userId(), 'schedules.manage'),
            'canRecordAttendance' => $service->can($this->userId(), 'schedules.attendance'),
            'canViewApplicant' => $service->can($this->userId(), 'candidates.view'),
            'canViewAll' => $service->can($this->userId(), 'schedules.view_all'),
            'stages' => db_connect()->table('recruitment_stages')->where('is_schedulable', 1)->where('is_active', 1)->orderBy('display_order')->get()->getResultArray(),
            'success' => session()->getFlashdata('agenda_success'), 'error' => session()->getFlashdata('agenda_error')]);
    }

    private function success(int $id, string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/agenda/' . $id))->with('agenda_success', $message);
    }

    private function failure(InvalidArgumentException $exception, ?int $id = null): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/agenda' . ($id === null ? '' : '/' . $id)))->with('agenda_error', $exception->getMessage());
    }
}
