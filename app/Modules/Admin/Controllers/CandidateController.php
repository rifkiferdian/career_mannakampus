<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class CandidateController extends BaseController
{
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
        $database = db_connect();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $canManageTeams = Services::authorization()->can($userId, 'hrd.teams.manage');
        $currentTeam = $this->currentTeam($userId);
        $teams = $database->table('hrd_teams')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $requestedTeamId = max(0, (int) $this->request->getGet('team_id'));
        if ($canManageTeams) {
            $validTeamIds = array_map('intval', array_column($teams, 'id'));
            $selectedTeamId = in_array($requestedTeamId, $validTeamIds, true)
                ? $requestedTeamId
                : (int) ($currentTeam['id'] ?? ($teams[0]['id'] ?? 0));
        } else {
            $selectedTeamId = (int) ($currentTeam['id'] ?? 0);
        }
        $selectedTeam = null;
        foreach ($teams as $team) {
            if ((int) $team['id'] === $selectedTeamId) {
                $selectedTeam = $team;
                break;
            }
        }
        $stages = $database->table('recruitment_stages')->where('is_active', 1)->orderBy('display_order')->get()->getResultArray();
        $statusOptions = [
            'screening_passed' => 'Lolos screening',
            'screening_failed' => 'Tidak lolos screening',
        ];
        foreach ($stages as $stage) {
            $statusOptions[(string) $stage['code']] = (string) $stage['name'];
        }
        $filters = $this->filters(array_keys($statusOptions));
        $builder = $this->candidateQuery();
        if ($selectedTeamId > 0) {
            $builder->where('applicants.assigned_hrd_team_id', $selectedTeamId);
        } else {
            $builder->where('applications.id', 0);
        }
        $this->applyFilters($builder, $filters);
        $applications = $builder->orderBy('applications.updated_at', 'DESC')->orderBy('applications.id', 'DESC')->get()->getResultArray();
        $now = time();
        foreach ($applications as &$application) {
            $since = strtotime((string) ($application['stage_changed_at'] ?: $application['submitted_at']));
            $application['days_in_stage'] = $since === false ? 0 : max(0, (int) floor(($now - $since) / 86400));
            $application['is_overdue'] = (int) $application['sla_days'] > 0 && $application['days_in_stage'] > (int) $application['sla_days'];
            $application['status_label'] = $this->statusLabel((string) $application['application_status'], $stages);
            $application['stage_color'] = $this->stageColor((string) $application['application_status'], $stages);
        }
        unset($application);
        $canUpdateStatus = Services::authorization()->can($userId, 'candidates.status.update')
            && $selectedTeamId > 0
            && ($canManageTeams || (int) ($currentTeam['id'] ?? 0) === $selectedTeamId);

        return view('admin/candidates', [
            'auth' => $auth,
            'applications' => $applications,
            'stages' => $stages,
            'statusOptions' => $statusOptions,
            'filters' => $filters,
            'vacancies' => $database->table('vacancies')->select('id, title')->where('deleted_at', null)->orderBy('title')->get()->getResultArray(),
            'periods' => $database->table('vacancy_recruitment_periods AS periods')->select('periods.id, periods.period_name, vacancies.title AS vacancy_title')->join('vacancies', 'vacancies.id = periods.vacancy_id')->where('periods.deleted_at', null)->orderBy('periods.opened_at', 'DESC')->get()->getResultArray(),
            'departments' => $database->table('departments')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'rejectionTemplates' => $database->table('rejection_reason_templates')->where('is_active', 1)->orderBy('display_order')->get()->getResultArray(),
            'teams' => $teams,
            'currentTeam' => $currentTeam,
            'selectedTeam' => $selectedTeam,
            'selectedTeamId' => $selectedTeamId,
            'canManageTeams' => $canManageTeams,
            'canUpdateStatus' => $canUpdateStatus,
            'summary' => [
                'total' => count($applications),
                'overdue' => count(array_filter($applications, static fn (array $row): bool => (bool) $row['is_overdue'])),
                'accepted' => count(array_filter($applications, static fn (array $row): bool => in_array($row['application_status'], ['accepted', 'hired'], true))),
                'active' => count(array_filter($applications, static fn (array $row): bool => ! in_array($row['application_status'], ['accepted', 'hired', 'rejected', 'withdrawn', 'screening_failed'], true))),
            ],
            'success' => session()->getFlashdata('candidate_success'),
            'error' => session()->getFlashdata('candidate_error'),
        ]);
    }

    public function updateStage(int $applicationId): RedirectResponse
    {
        $database = db_connect();
        $application = $database->table('applications AS applications')
            ->select('applications.*, applicants.assigned_hrd_team_id AS owner_hrd_team_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->where('applications.id', $applicationId)
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null)
            ->get()->getRowArray();
        $userId = (int) (session()->get('hrd_auth')['user_id'] ?? 0);
        $currentTeam = $this->currentTeam($userId);
        $canManageTeams = Services::authorization()->can($userId, 'hrd.teams.manage');
        $returnTeamId = max(0, (int) $this->request->getPost('team_id'));
        $newStage = trim((string) $this->request->getPost('stage'));
        $stage = $database->table('recruitment_stages')->where('code', $newStage)->where('is_active', 1)->get()->getRowArray();
        if ($application === null || $stage === null) {
            return $this->candidateError('Lamaran atau tahapan yang dipilih tidak valid.', $returnTeamId);
        }
        if (empty($application['owner_hrd_team_id'])) {
            return $this->candidateError('Pelamar belum dipilih oleh divisi HRD.', $returnTeamId);
        }
        if (! $canManageTeams && (int) ($currentTeam['id'] ?? 0) !== (int) $application['owner_hrd_team_id']) {
            return $this->candidateError('Pelamar ini dimiliki divisi HRD lain dan tidak dapat Anda proses.', $returnTeamId);
        }
        if ((string) $application['application_status'] === $newStage) {
            return $this->candidateError('Kandidat sudah berada pada tahapan tersebut.', $returnTeamId);
        }

        $notes = mb_substr(trim((string) $this->request->getPost('notes')), 0, 2000);
        $publicMessage = 'Lamaran Anda saat ini berada pada tahap ' . $stage['name'] . '.';
        if ($newStage === 'rejected') {
            $templateId = (int) $this->request->getPost('rejection_template_id');
            $template = $database->table('rejection_reason_templates')->where('id', $templateId)->where('is_active', 1)->get()->getRowArray();
            if ($template === null) {
                return $this->candidateError('Pilih template alasan penolakan terlebih dahulu.', $returnTeamId);
            }
            $publicMessage = (string) $template['reason_text'];
            $notes = 'Alasan penolakan: ' . $template['title'] . ($notes !== '' ? '. Catatan: ' . $notes : '');
        } elseif ($newStage === 'accepted') {
            $publicMessage = 'Selamat, lamaran Anda dinyatakan diterima. Tim HRD akan menghubungi Anda untuk proses berikutnya.';
        }

        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('applications')->where('id', $applicationId)->update([
            'application_status' => $newStage,
            'public_message' => mb_substr($publicMessage, 0, 500),
            'reviewed_at' => $now,
            'reviewed_by' => $userId,
            'updated_at' => $now,
        ]);
        $database->table('application_status_histories')->insert([
            'application_id' => $applicationId,
            'status_type' => 'application',
            'previous_status' => (string) $application['application_status'],
            'new_status' => $newStage,
            'notes' => $notes !== '' ? $notes : 'Tahapan kandidat diubah menjadi ' . $stage['name'] . '.',
            'changed_by' => $userId,
            'created_at' => $now,
        ]);
        $database->transComplete();
        if (! $database->transStatus()) {
            return $this->candidateError('Tahapan kandidat gagal diperbarui.', $returnTeamId);
        }

        return redirect()->to($this->candidateUrl($returnTeamId))->with('candidate_success', 'Tahapan kandidat berhasil diubah menjadi ' . $stage['name'] . '.');
    }

    private function candidateQuery(): BaseBuilder
    {
        return db_connect()->table('applications AS applications')
            ->select('applications.id, applications.applicant_id, applications.application_number, applications.screening_status, applications.screening_score, applications.application_status, applicants.assigned_hrd_team_id, applicants.assigned_at, applications.submitted_at, applications.updated_at, applicants.full_name, applicants.email, applicants.phone, vacancies.title AS vacancy_title, vacancies.department_id, periods.period_name, departments.name AS department_name, teams.name AS hrd_team_name, assigned_user.full_name AS assigned_by_name, stages.sla_days, stages.color_hex, (SELECT MAX(histories.created_at) FROM application_status_histories histories WHERE histories.application_id = applications.id) AS stage_changed_at', false)
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('vacancy_recruitment_periods AS periods', 'periods.id = applications.vacancy_period_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id')
            ->join('users AS assigned_user', 'assigned_user.id = applicants.assigned_by_user_id', 'left')
            ->join('recruitment_stages AS stages', 'stages.code = applications.application_status', 'left')
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
    }

    /** @param list<string> $statuses
     * @return array{keyword: string, vacancy_id: int, vacancy_period_id: int, department_id: int, status: string}
     */
    private function filters(array $statuses): array
    {
        $status = trim((string) $this->request->getGet('status'));

        return [
            'keyword' => mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100),
            'vacancy_id' => max(0, (int) $this->request->getGet('vacancy_id')),
            'vacancy_period_id' => max(0, (int) $this->request->getGet('vacancy_period_id')),
            'department_id' => max(0, (int) $this->request->getGet('department_id')),
            'status' => in_array($status, $statuses, true) ? $status : '',
        ];
    }

    /** @param array{keyword: string, vacancy_id: int, vacancy_period_id: int, department_id: int, status: string} $filters */
    private function applyFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['keyword'] !== '') {
            $builder->groupStart()->like('applicants.full_name', $filters['keyword'])->orLike('applicants.email', $filters['keyword'])->orLike('applicants.phone', $filters['keyword'])->orLike('applications.application_number', $filters['keyword'])->groupEnd();
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
    }

    /** @param list<array<string, mixed>> $stages */
    private function statusLabel(string $status, array $stages): string
    {
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

        return $status === 'screening_failed' ? '#DC2626' : '#64748B';
    }

    private function candidateError(string $message, int $teamId = 0): RedirectResponse
    {
        return redirect()->to($this->candidateUrl($teamId))->with('candidate_error', $message);
    }

    /** @return array<string, mixed>|null */
    private function currentTeam(int $userId): ?array
    {
        return db_connect()->table('hrd_team_users AS team_users')
            ->select('teams.id, teams.name, teams.code')
            ->join('hrd_teams AS teams', 'teams.id = team_users.hrd_team_id')
            ->where('team_users.user_id', $userId)
            ->where('teams.is_active', 1)
            ->get()->getRowArray() ?: null;
    }

    private function candidateUrl(int $teamId): string
    {
        $url = site_url('adminhrdmannakampus/kandidat');

        return $teamId > 0 ? $url . '?team_id=' . $teamId : $url;
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
