<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class TalentPoolController extends BaseController
{
    private const STATUSES = [
        'available' => 'Tersedia',
        'contacted' => 'Sedang dihubungi',
        'processing' => 'Bersedia diproses',
        'unavailable' => 'Tidak tersedia',
        'hired' => 'Sudah direkrut',
        'archived' => 'Diarsipkan',
    ];

    private const PRIORITIES = [
        'high' => 'Tinggi',
        'normal' => 'Normal',
        'low' => 'Rendah',
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
        $selectedTeamId = $this->selectedTeamId($teams, $currentTeam, $canManageTeams);
        $selectedTeam = null;
        foreach ($teams as $team) {
            if ((int) $team['id'] === $selectedTeamId) {
                $selectedTeam = $team;
                break;
            }
        }

        $filters = $this->filters();
        $builder = $database->table('talent_pool_candidates AS pool')
            ->select('pool.*, applicants.full_name, applicants.email, applicants.phone, applicants.birth_date, applications.application_number, applications.application_status AS source_application_status, source_vacancy.title AS source_vacancy_title, source_department.name AS source_department_name, target_department.name AS target_department_name, teams.name AS hrd_team_name, saved_user.full_name AS saved_by_name')
            ->join('applicants', 'applicants.id = pool.applicant_id')
            ->join('applications', 'applications.id = pool.application_id')
            ->join('vacancies AS source_vacancy', 'source_vacancy.id = applications.vacancy_id')
            ->join('departments AS source_department', 'source_department.id = source_vacancy.department_id')
            ->join('departments AS target_department', 'target_department.id = pool.target_department_id', 'left')
            ->join('hrd_teams AS teams', 'teams.id = pool.hrd_team_id', 'left')
            ->join('users AS saved_user', 'saved_user.id = pool.saved_by', 'left')
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
        if ($selectedTeamId > 0) {
            $builder->where('pool.hrd_team_id', $selectedTeamId);
        } else {
            $builder->where('pool.id', 0);
        }
        $this->applyFilters($builder, $filters);
        $candidates = $builder->orderBy('pool.updated_at', 'DESC')->orderBy('pool.id', 'DESC')->get()->getResultArray();

        $today = new \DateTimeImmutable('today');
        foreach ($candidates as &$candidate) {
            $number = preg_replace('/\D+/', '', (string) ($candidate['phone'] ?? '')) ?? '';
            if (str_starts_with($number, '0')) {
                $number = '62' . substr($number, 1);
            } elseif (str_starts_with($number, '8')) {
                $number = '62' . $number;
            }
            $candidate['whatsapp_number'] = $number;
            $birthDate = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($candidate['birth_date'] ?? ''));
            $candidate['age'] = $birthDate !== false && $birthDate <= $today ? $birthDate->diff($today)->y : null;
            $candidate['status_label'] = self::STATUSES[(string) $candidate['pool_status']] ?? 'Tidak diketahui';
            $candidate['priority_label'] = self::PRIORITIES[(string) $candidate['priority']] ?? 'Normal';
            $candidate['is_follow_up_due'] = ! empty($candidate['follow_up_at'])
                && (string) $candidate['follow_up_at'] <= $today->format('Y-m-d')
                && ! in_array((string) $candidate['pool_status'], ['hired', 'archived'], true);
        }
        unset($candidate);

        $historiesByCandidate = [];
        $candidateIds = array_map('intval', array_column($candidates, 'id'));
        if ($candidateIds !== []) {
            foreach ($database->table('talent_pool_histories AS histories')
                ->select('histories.*, users.full_name AS changed_by_name, vacancies.title AS related_vacancy_title, related_application.application_number AS related_application_number')
                ->join('users', 'users.id = histories.changed_by', 'left')
                ->join('vacancies', 'vacancies.id = histories.related_vacancy_id', 'left')
                ->join('applications AS related_application', 'related_application.id = histories.related_application_id', 'left')
                ->whereIn('histories.talent_pool_candidate_id', $candidateIds)
                ->orderBy('histories.created_at', 'DESC')->orderBy('histories.id', 'DESC')->get()->getResultArray() as $history) {
                $historiesByCandidate[(int) $history['talent_pool_candidate_id']][] = $history;
            }
        }

        $canManage = Services::authorization()->can($userId, 'candidates.status.update')
            && $selectedTeamId > 0
            && ($canManageTeams || (int) ($currentTeam['id'] ?? 0) === $selectedTeamId);

        return view('admin/talent_pool', [
            'auth' => $auth,
            'candidates' => $candidates,
            'historiesByCandidate' => $historiesByCandidate,
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'filters' => $filters,
            'departments' => $database->table('departments')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'recruitmentPeriods' => $database->table('vacancy_recruitment_periods AS periods')
                ->select('periods.id, periods.period_name, periods.status AS period_status, vacancies.id AS vacancy_id, vacancies.title AS vacancy_title')
                ->join('vacancies', 'vacancies.id = periods.vacancy_id')
                ->whereIn('periods.status', ['open', 'scheduled'])
                ->where('periods.deleted_at', null)->where('vacancies.deleted_at', null)->where('vacancies.status !=', 'archived')
                ->orderBy('vacancies.title')->orderBy('periods.opened_at', 'DESC')->get()->getResultArray(),
            'teams' => $teams,
            'currentTeam' => $currentTeam,
            'selectedTeam' => $selectedTeam,
            'selectedTeamId' => $selectedTeamId,
            'canManageTeams' => $canManageTeams,
            'canManage' => $canManage,
            'summary' => [
                'total' => count($candidates),
                'available' => count(array_filter($candidates, static fn (array $row): bool => $row['pool_status'] === 'available')),
                'high' => count(array_filter($candidates, static fn (array $row): bool => $row['priority'] === 'high')),
                'due' => count(array_filter($candidates, static fn (array $row): bool => (bool) $row['is_follow_up_due'])),
            ],
            'success' => session()->getFlashdata('talent_pool_success'),
            'error' => session()->getFlashdata('talent_pool_error'),
        ]);
    }

    public function save(int $applicationId): RedirectResponse
    {
        $database = db_connect();
        $userId = $this->currentUserId();
        $application = $database->table('applications AS applications')
            ->select('applications.id, applications.applicant_id, applicants.assigned_hrd_team_id, vacancies.title AS vacancy_title, vacancies.department_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->where('applications.id', $applicationId)
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null)
            ->get()->getRowArray();
        $teamId = (int) ($application['assigned_hrd_team_id'] ?? 0);
        if ($application === null || ! $this->canManageTeam($userId, $teamId)) {
            return $this->saveError('Lamaran tidak ditemukan atau tidak dapat Anda kelola.', $teamId);
        }
        if ($database->table('talent_pool_candidates')->where('applicant_id', (int) $application['applicant_id'])->countAllResults() > 0) {
            return $this->saveError('Pelamar ini sudah tersimpan di Talent Pool melalui salah satu lamarannya.', $teamId);
        }

        $data = $this->validatedData((string) $application['vacancy_title'], (int) $application['department_id']);
        if (is_string($data)) {
            return $this->saveError($data, $teamId);
        }
        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('talent_pool_candidates')->insert($data + [
            'application_id' => $applicationId,
            'applicant_id' => (int) $application['applicant_id'],
            'hrd_team_id' => $teamId,
            'pool_status' => 'available',
            'last_contacted_at' => null,
            'saved_by' => $userId,
            'saved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $candidateId = (int) $database->insertID();
        $database->table('talent_pool_histories')->insert([
            'talent_pool_candidate_id' => $candidateId,
            'action_code' => 'saved',
            'previous_status' => null,
            'new_status' => 'available',
            'notes' => 'Disimpan sebagai kandidat cadangan. Alasan: ' . $data['reason'],
            'related_vacancy_id' => null,
            'related_application_id' => null,
            'changed_by' => $userId,
            'created_at' => $now,
        ]);
        $database->transComplete();

        return $database->transStatus()
            ? redirect()->to($this->candidateUrl($teamId))->with('candidate_success', 'Pelamar berhasil disimpan ke Talent Pool.')
            : $this->saveError('Pelamar gagal disimpan ke Talent Pool.', $teamId);
    }

    public function update(int $candidateId): RedirectResponse
    {
        $database = db_connect();
        $userId = $this->currentUserId();
        $candidate = $this->candidate($candidateId);
        $teamId = (int) ($candidate['hrd_team_id'] ?? 0);
        if ($candidate === null || ! $this->canManageTeam($userId, $teamId)) {
            return $this->error('Data Talent Pool tidak ditemukan atau tidak dapat Anda kelola.', $teamId);
        }
        $data = $this->validatedData((string) $candidate['recommended_position'], (int) ($candidate['target_department_id'] ?? 0));
        if (is_string($data)) {
            return $this->error($data, $teamId);
        }
        $status = trim((string) $this->request->getPost('pool_status'));
        if (! isset(self::STATUSES[$status])) {
            return $this->error('Status Talent Pool tidak valid.', $teamId);
        }
        $previousStatus = (string) $candidate['pool_status'];
        $changes = [];
        if ($previousStatus !== $status) {
            $changes[] = 'Status diubah dari ' . (self::STATUSES[$previousStatus] ?? $previousStatus) . ' menjadi ' . self::STATUSES[$status] . '.';
        }
        if ((string) $candidate['recommended_position'] !== (string) $data['recommended_position']) {
            $changes[] = 'Posisi rekomendasi diubah dari ' . $candidate['recommended_position'] . ' menjadi ' . $data['recommended_position'] . '.';
        }
        if ((string) $candidate['priority'] !== (string) $data['priority']) {
            $changes[] = 'Prioritas diubah dari ' . (self::PRIORITIES[(string) $candidate['priority']] ?? $candidate['priority']) . ' menjadi ' . self::PRIORITIES[(string) $data['priority']] . '.';
        }
        if ((int) ($candidate['target_department_id'] ?? 0) !== (int) $data['target_department_id']) {
            $changes[] = 'Departemen rekomendasi diperbarui.';
        }
        if ((string) $candidate['reason'] !== (string) $data['reason']) {
            $changes[] = 'Alasan penyimpanan diperbarui menjadi: ' . $data['reason'];
        }
        if ((string) ($candidate['strength_notes'] ?? '') !== (string) ($data['strength_notes'] ?? '')) {
            $changes[] = 'Catatan kelebihan kandidat diperbarui.';
        }
        if ((string) ($candidate['internal_notes'] ?? '') !== (string) ($data['internal_notes'] ?? '')) {
            $changes[] = 'Catatan internal diperbarui.';
        }
        if ((string) ($candidate['available_from'] ?? '') !== (string) ($data['available_from'] ?? '')) {
            $changes[] = 'Tanggal ketersediaan diperbarui menjadi ' . ($data['available_from'] ?? 'tidak ditentukan') . '.';
        }
        if ((string) ($candidate['follow_up_at'] ?? '') !== (string) ($data['follow_up_at'] ?? '')) {
            $changes[] = 'Tanggal tindak lanjut diperbarui menjadi ' . ($data['follow_up_at'] ?? 'tidak ditentukan') . '.';
        }
        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('talent_pool_candidates')->where('id', $candidateId)->update($data + ['pool_status' => $status, 'updated_at' => $now]);
        $database->table('talent_pool_histories')->insert([
            'talent_pool_candidate_id' => $candidateId,
            'action_code' => $previousStatus === $status ? 'updated' : 'status_changed',
            'previous_status' => $previousStatus,
            'new_status' => $status,
            'notes' => $changes !== [] ? implode(' ', $changes) : 'Data kandidat cadangan disimpan tanpa perubahan nilai.',
            'related_vacancy_id' => null,
            'related_application_id' => null,
            'changed_by' => $userId,
            'created_at' => $now,
        ]);
        $database->transComplete();

        return $database->transStatus()
            ? redirect()->to($this->url($teamId))->with('talent_pool_success', 'Data kandidat cadangan berhasil diperbarui.')
            : $this->error('Data kandidat cadangan gagal diperbarui.', $teamId);
    }

    public function contact(int $candidateId): RedirectResponse
    {
        $database = db_connect();
        $userId = $this->currentUserId();
        $candidate = $this->candidate($candidateId);
        $teamId = (int) ($candidate['hrd_team_id'] ?? 0);
        if ($candidate === null || ! $this->canManageTeam($userId, $teamId)) {
            return $this->error('Data Talent Pool tidak ditemukan atau tidak dapat Anda kelola.', $teamId);
        }
        if ((string) $this->request->getPost('candidate_confirmed') !== '1') {
            return $this->error('Konfirmasikan bahwa kandidat sudah bersedia diproses untuk lowongan baru.', $teamId);
        }
        $vacancyPeriodId = (int) $this->request->getPost('vacancy_period_id');
        $period = $database->table('vacancy_recruitment_periods AS periods')
            ->select('periods.id, periods.vacancy_id, periods.period_name, vacancies.title AS vacancy_title')
            ->join('vacancies', 'vacancies.id = periods.vacancy_id')
            ->where('periods.id', $vacancyPeriodId)->whereIn('periods.status', ['open', 'scheduled'])
            ->where('periods.deleted_at', null)->where('vacancies.deleted_at', null)->where('vacancies.status !=', 'archived')->get()->getRowArray();
        if ($period === null) {
            return $this->error('Pilih sesi lowongan tujuan yang masih aktif.', $teamId);
        }
        $notes = mb_substr(trim((string) $this->request->getPost('contact_notes')), 0, 1000);
        if (mb_strlen($notes) < 5) {
            return $this->error('Catatan pemanggilan minimal 5 karakter.', $teamId);
        }
        $followUpAt = $this->dateInput('follow_up_at');
        if ($followUpAt === false) {
            return $this->error('Tanggal tindak lanjut tidak valid.', $teamId);
        }
        $now = date('Y-m-d H:i:s');
        $previousStatus = (string) $candidate['pool_status'];
        $existingApplication = $database->table('applications')
            ->select('id, application_number')->where('applicant_id', (int) $candidate['applicant_id'])
            ->where('vacancy_period_id', $vacancyPeriodId)->where('deleted_at', null)->get()->getRowArray();
        $relatedApplicationId = (int) ($existingApplication['id'] ?? 0);
        $applicationNumber = (string) ($existingApplication['application_number'] ?? '');
        $applicationWasCreated = false;
        $database->transStart();
        if ($existingApplication === null) {
            $source = $database->table('applications AS applications')
                ->select('applications.*, batches.requirement_group_id, batches.applicant_snapshot, batches.snapshot_version')
                ->join('application_batches AS batches', 'batches.id = applications.batch_id')
                ->where('applications.id', (int) $candidate['application_id'])->get()->getRowArray();
            if ($source === null) {
                $database->transRollback();
                return $this->error('Data sumber lamaran tidak lengkap sehingga lamaran baru tidak dapat dibuat.', $teamId);
            }
            $batchUuid = $this->uuidV4();
            $batchNumber = 'MKB-TP-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $database->table('application_batches')->insert([
                'uuid' => $batchUuid,
                'batch_number' => $batchNumber,
                'applicant_id' => (int) $candidate['applicant_id'],
                'requirement_group_id' => $source['requirement_group_id'],
                'position_count' => 1,
                'applicant_snapshot' => $source['applicant_snapshot'],
                'snapshot_version' => $source['snapshot_version'],
                'submitted_at' => $now,
                'submitted_ip' => $this->request->getIPAddress(),
                'submitted_user_agent' => mb_substr('Talent Pool invitation: ' . (string) $this->request->getUserAgent(), 0, 500),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $batchId = (int) $database->insertID();
            $workExperiences = $database->table('application_work_experiences')->where('batch_id', (int) $source['batch_id'])->orderBy('display_order')->get()->getResultArray();
            foreach ($workExperiences as $experience) {
                unset($experience['id']);
                $experience['batch_id'] = $batchId;
                $experience['created_at'] = $now;
                $experience['updated_at'] = $now;
                $database->table('application_work_experiences')->insert($experience);
            }
            $applicationNumber = 'TP-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $database->table('applications')->insert([
                'uuid' => $this->uuidV4(),
                'application_number' => $applicationNumber,
                'tracking_token_hash' => hash('sha256', bin2hex(random_bytes(32))),
                'batch_id' => $batchId,
                'applicant_id' => (int) $candidate['applicant_id'],
                'vacancy_id' => (int) $period['vacancy_id'],
                'vacancy_period_id' => $vacancyPeriodId,
                'preference_order' => 1,
                'work_experience' => $source['work_experience'],
                'work_motivation' => $source['work_motivation'],
                'career_goal' => $source['career_goal'],
                'screening_status' => 'pending',
                'screening_score' => null,
                'screening_notes' => null,
                'public_message' => 'Tim HRD mengundang Anda untuk mengikuti proses lowongan ' . $period['vacancy_title'] . '.',
                'application_status' => 'lamaran_baru',
                'submitted_at' => $now,
                'submitted_ip' => $this->request->getIPAddress(),
                'submitted_user_agent' => mb_substr('Talent Pool invitation by HRD', 0, 500),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
            $relatedApplicationId = (int) $database->insertID();
            $applicationWasCreated = true;
            $database->table('application_status_histories')->insert([
                'application_id' => $relatedApplicationId,
                'status_type' => 'application',
                'previous_status' => null,
                'new_status' => 'lamaran_baru',
                'notes' => 'Lamaran dibuat dari Talent Pool setelah kandidat menyetujui pemanggilan untuk lowongan ' . $period['vacancy_title'] . '.',
                'changed_by' => $userId,
                'created_at' => $now,
            ]);
        }
        $database->table('talent_pool_candidates')->where('id', $candidateId)->update([
            'pool_status' => 'processing',
            'follow_up_at' => $followUpAt,
            'last_contacted_at' => $now,
            'updated_at' => $now,
        ]);
        $database->table('talent_pool_histories')->insert([
            'talent_pool_candidate_id' => $candidateId,
            'action_code' => 'invited_to_vacancy',
            'previous_status' => $previousStatus,
            'new_status' => 'processing',
            'notes' => 'Kandidat menyetujui pemanggilan untuk lowongan ' . $period['vacancy_title'] . ' (' . $period['period_name'] . '). Nomor lamaran: ' . $applicationNumber . '. ' . $notes,
            'related_vacancy_id' => (int) $period['vacancy_id'],
            'related_application_id' => $relatedApplicationId,
            'changed_by' => $userId,
            'created_at' => $now,
        ]);
        $database->transComplete();

        if (! $database->transStatus()) {
            return $this->error('Pemanggilan kandidat dan pembuatan lamaran baru gagal.', $teamId);
        }

        $message = $applicationWasCreated
            ? 'Kandidat berhasil dipanggil dan lamaran ' . $applicationNumber . ' telah dibuat.'
            : 'Kandidat berhasil dipanggil. Lamaran ' . $applicationNumber . ' untuk sesi tersebut sudah tersedia dan tidak dibuat ulang.';

        return redirect()->to($this->url($teamId))->with('talent_pool_success', $message);
    }

    /** @return array<string, mixed>|string */
    private function validatedData(string $defaultPosition, int $defaultDepartmentId): array|string
    {
        $position = mb_substr(trim((string) $this->request->getPost('recommended_position')), 0, 150);
        $position = $position !== '' ? $position : $defaultPosition;
        $departmentId = (int) $this->request->getPost('target_department_id');
        $departmentId = $departmentId > 0 ? $departmentId : $defaultDepartmentId;
        $priority = trim((string) $this->request->getPost('priority'));
        $reason = mb_substr(trim((string) $this->request->getPost('reason')), 0, 1000);
        $strengthNotes = mb_substr(trim((string) $this->request->getPost('strength_notes')), 0, 4000);
        $internalNotes = mb_substr(trim((string) $this->request->getPost('internal_notes')), 0, 4000);
        $availableFrom = $this->dateInput('available_from');
        $followUpAt = $this->dateInput('follow_up_at');

        if (mb_strlen($position) < 3) {
            return 'Posisi rekomendasi minimal 3 karakter.';
        }
        if (! isset(self::PRIORITIES[$priority])) {
            return 'Prioritas kandidat tidak valid.';
        }
        if (mb_strlen($reason) < 5) {
            return 'Alasan menyimpan kandidat minimal 5 karakter.';
        }
        if ($departmentId <= 0 || db_connect()->table('departments')->where('id', $departmentId)->where('is_active', 1)->countAllResults() === 0) {
            return 'Pilih departemen rekomendasi yang valid.';
        }
        if ($availableFrom === false || $followUpAt === false) {
            return 'Tanggal ketersediaan atau tindak lanjut tidak valid.';
        }

        return [
            'target_department_id' => $departmentId,
            'recommended_position' => $position,
            'priority' => $priority,
            'reason' => $reason,
            'strength_notes' => $strengthNotes !== '' ? $strengthNotes : null,
            'internal_notes' => $internalNotes !== '' ? $internalNotes : null,
            'available_from' => $availableFrom,
            'follow_up_at' => $followUpAt,
        ];
    }

    /** @return array{keyword: string, pool_status: string, priority: string, department_id: int} */
    private function filters(): array
    {
        $status = trim((string) $this->request->getGet('pool_status'));
        $priority = trim((string) $this->request->getGet('priority'));
        return [
            'keyword' => mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100),
            'pool_status' => isset(self::STATUSES[$status]) ? $status : '',
            'priority' => isset(self::PRIORITIES[$priority]) ? $priority : '',
            'department_id' => max(0, (int) $this->request->getGet('department_id')),
        ];
    }

    /** @param array{keyword: string, pool_status: string, priority: string, department_id: int} $filters */
    private function applyFilters(\CodeIgniter\Database\BaseBuilder $builder, array $filters): void
    {
        if ($filters['keyword'] !== '') {
            $builder->groupStart()->like('applicants.full_name', $filters['keyword'])->orLike('applicants.email', $filters['keyword'])->orLike('applicants.phone', $filters['keyword'])->orLike('pool.recommended_position', $filters['keyword'])->groupEnd();
        }
        if ($filters['pool_status'] !== '') {
            $builder->where('pool.pool_status', $filters['pool_status']);
        }
        if ($filters['priority'] !== '') {
            $builder->where('pool.priority', $filters['priority']);
        }
        if ($filters['department_id'] > 0) {
            $builder->where('pool.target_department_id', $filters['department_id']);
        }
    }

    /** @return array<string, mixed>|null */
    private function candidate(int $candidateId): ?array
    {
        return db_connect()->table('talent_pool_candidates')->where('id', $candidateId)->get()->getRowArray() ?: null;
    }

    /** @return string|null|false */
    private function dateInput(string $field): string|null|false
    {
        $value = trim((string) $this->request->getPost($field));
        if ($value === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : false;
    }

    /** @param list<array<string, mixed>> $teams @param array<string, mixed>|null $currentTeam */
    private function selectedTeamId(array $teams, ?array $currentTeam, bool $canManageTeams): int
    {
        if (! $canManageTeams) {
            return (int) ($currentTeam['id'] ?? 0);
        }
        $requested = max(0, (int) $this->request->getGet('team_id'));
        $validIds = array_map('intval', array_column($teams, 'id'));

        return in_array($requested, $validIds, true) ? $requested : (int) ($currentTeam['id'] ?? ($teams[0]['id'] ?? 0));
    }

    private function canManageTeam(int $userId, int $teamId): bool
    {
        if ($teamId <= 0 || ! Services::authorization()->can($userId, 'candidates.status.update')) {
            return false;
        }

        return Services::authorization()->can($userId, 'hrd.teams.manage') || (int) ($this->currentTeam($userId)['id'] ?? 0) === $teamId;
    }

    /** @return array<string, mixed>|null */
    private function currentTeam(int $userId): ?array
    {
        return db_connect()->table('hrd_team_users AS team_users')
            ->select('teams.id, teams.name, teams.code')
            ->join('hrd_teams AS teams', 'teams.id = team_users.hrd_team_id')
            ->where('team_users.user_id', $userId)->where('teams.is_active', 1)->get()->getRowArray() ?: null;
    }

    private function currentUserId(): int
    {
        return (int) (session()->get('hrd_auth')['user_id'] ?? 0);
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }

    private function candidateUrl(int $teamId): string
    {
        return site_url('adminhrdmannakampus/kandidat') . ($teamId > 0 ? '?team_id=' . $teamId : '');
    }

    private function url(int $teamId): string
    {
        return site_url('adminhrdmannakampus/talent-pool') . ($teamId > 0 ? '?team_id=' . $teamId : '');
    }

    private function error(string $message, int $teamId): RedirectResponse
    {
        return redirect()->to($this->url($teamId))->with('talent_pool_error', $message);
    }

    private function saveError(string $message, int $teamId): RedirectResponse
    {
        return redirect()->to($this->candidateUrl($teamId))->with('candidate_error', $message);
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
