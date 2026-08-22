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
        $templateStages = $this->templateStages();
        $statusOptions = [
            'lamaran_baru' => 'Lamaran Baru',
            'screening_passed' => 'Lolos screening',
            'screening_failed' => 'Tidak lolos screening',
        ];
        foreach ($stages as $stage) {
            $statusOptions[(string) $stage['code']] = (string) $stage['name'];
        }
        $rejectionStageOptions = [];
        foreach ($templateStages as $sequence) {
            foreach ($sequence as $stage) {
                if (! in_array((string) $stage['code'], ['accepted', 'rejected'], true)) {
                    $rejectionStageOptions[(string) $stage['code']] = $this->rejectionStageLabel($stage);
                }
            }
        }
        foreach ($database->table('application_rejections')->select('stage_code, stage_name_snapshot, stage_order_snapshot')->distinct()->get()->getResultArray() as $rejection) {
            $rejectionStageOptions[(string) $rejection['stage_code']] = $this->rejectionStageLabel([
                'name' => $rejection['stage_name_snapshot'],
                'display_order' => $rejection['stage_order_snapshot'],
            ]);
        }
        asort($rejectionStageOptions);
        $filters = $this->filters(array_keys($statusOptions), array_keys($rejectionStageOptions));
        $builder = $this->candidateQuery();
        if ($selectedTeamId > 0) {
            $builder->where('applicants.assigned_hrd_team_id', $selectedTeamId);
        } else {
            $builder->where('applications.id', 0);
        }
        $this->applyFilters($builder, $filters);
        $applications = $builder->orderBy('applications.updated_at', 'DESC')->orderBy('applications.id', 'DESC')->get()->getResultArray();
        $now = time();
        $today = new \DateTimeImmutable('today');
        foreach ($applications as &$application) {
            $whatsAppNumber = preg_replace('/\D+/', '', (string) ($application['phone'] ?? '')) ?? '';
            if (str_starts_with($whatsAppNumber, '0')) {
                $whatsAppNumber = '62' . substr($whatsAppNumber, 1);
            } elseif (str_starts_with($whatsAppNumber, '8')) {
                $whatsAppNumber = '62' . $whatsAppNumber;
            }
            $application['whatsapp_number'] = $whatsAppNumber;
            $birthDate = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($application['birth_date'] ?? ''));
            $application['age'] = $birthDate !== false && $birthDate <= $today
                ? $birthDate->diff($today)->y
                : null;
            $since = strtotime((string) ($application['stage_changed_at'] ?: $application['submitted_at']));
            $application['days_in_stage'] = $since === false ? 0 : max(0, (int) floor(($now - $since) / 86400));
            $application['is_overdue'] = (int) $application['sla_days'] > 0 && $application['days_in_stage'] > (int) $application['sla_days'];
            $application['status_label'] = $this->statusLabel((string) $application['application_status'], $stages);
            $application['stage_color'] = $this->stageColor((string) $application['application_status'], $stages);
            $application['available_stages'] = $this->nextStages((int) $application['recruitment_process_template_id'], (string) $application['application_status'], $templateStages, $stages);
            $application['process_steps'] = $this->processSteps(
                (int) $application['recruitment_process_template_id'],
                (string) $application['application_status'],
                $templateStages
            );
            $application['rejection_stage_label'] = $this->rejectionStageLabel($this->rejectionStageSnapshot(
                (int) $application['recruitment_process_template_id'],
                (string) $application['application_status'],
                $templateStages,
                $stages
            ));
        }
        unset($application);
        $canUpdateStatus = Services::authorization()->can($userId, 'candidates.status.update')
            && $selectedTeamId > 0
            && ($canManageTeams || (int) ($currentTeam['id'] ?? 0) === $selectedTeamId);
        $canCancelAssignment = Services::authorization()->can($userId, 'applicants.assign')
            && $selectedTeamId > 0
            && ($canManageTeams || (int) ($currentTeam['id'] ?? 0) === $selectedTeamId);

        return view('admin/candidates', [
            'auth' => $auth,
            'applications' => $applications,
            'stages' => $stages,
            'statusOptions' => $statusOptions,
            'rejectionStageOptions' => $rejectionStageOptions,
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
            'canCancelAssignment' => $canCancelAssignment,
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

    public function cancelAssignment(int $applicantId): RedirectResponse
    {
        $database = db_connect();
        $userId = (int) (session()->get('hrd_auth')['user_id'] ?? 0);
        $currentTeam = $this->currentTeam($userId);
        $canManageTeams = Services::authorization()->can($userId, 'hrd.teams.manage');
        $returnTeamId = max(0, (int) $this->request->getPost('team_id'));
        $applicant = $database->table('applicants AS applicants')
            ->select('applicants.id, applicants.full_name, applicants.assigned_hrd_team_id, teams.name AS hrd_team_name')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id', 'left')
            ->where('applicants.id', $applicantId)
            ->where('applicants.deleted_at', null)
            ->get()->getRowArray();

        if ($applicant === null || empty($applicant['assigned_hrd_team_id'])) {
            return $this->candidateError('Pelamar sudah berstatus Belum dipilih atau data tidak lagi tersedia.', $returnTeamId);
        }

        $assignedTeamId = (int) $applicant['assigned_hrd_team_id'];
        if (! $canManageTeams && (int) ($currentTeam['id'] ?? 0) !== $assignedTeamId) {
            return $this->candidateError('Pelamar ini dimiliki divisi HRD lain dan tidak dapat Anda batalkan.', $returnTeamId);
        }

        $applications = $database->table('applications')
            ->select('id, application_status')
            ->where('applicant_id', $applicantId)
            ->where('deleted_at', null)
            ->get()->getResultArray();
        $applicationIds = array_map('intval', array_column($applications, 'id'));

        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('applicants')
            ->where('id', $applicantId)
            ->where('assigned_hrd_team_id', $assignedTeamId)
            ->update([
                'assigned_hrd_team_id' => null,
                'assigned_by_user_id' => null,
                'assigned_at' => null,
                'updated_at' => $now,
            ]);
        $cancelled = $database->affectedRows() === 1;
        $talentPoolRemoved = false;
        if ($cancelled) {
            $database->table('talent_pool_candidates')
                ->where('applicant_id', $applicantId)
                ->delete();
            $talentPoolRemoved = $database->affectedRows() > 0;

            if ($applicationIds !== []) {
                $database->table('application_rejections')
                    ->whereIn('application_id', $applicationIds)
                    ->delete();
                $database->table('application_screening_answers')
                    ->whereIn('application_id', $applicationIds)
                    ->update([
                        'is_eligible' => null,
                        'score' => null,
                        'updated_at' => $now,
                    ]);
                $database->table('applications')
                    ->whereIn('id', $applicationIds)
                    ->update([
                        'application_status' => 'lamaran_baru',
                        'screening_status' => 'pending',
                        'screening_score' => null,
                        'screening_notes' => null,
                        'public_message' => 'Lamaran Anda telah diterima dan menunggu pemeriksaan oleh tim HRD.',
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                        'updated_at' => $now,
                    ]);

                $stageHistories = [];
                foreach ($applications as $application) {
                    if ((string) $application['application_status'] === 'lamaran_baru') {
                        continue;
                    }
                    $stageHistories[] = [
                        'application_id' => (int) $application['id'],
                        'status_type' => 'application',
                        'previous_status' => (string) $application['application_status'],
                        'new_status' => 'lamaran_baru',
                        'notes' => 'Alur rekrutmen direset ke Lamaran Baru karena pilihan divisi HRD dibatalkan.',
                        'changed_by' => $userId,
                        'created_at' => $now,
                    ];
                }
                if ($stageHistories !== []) {
                    $database->table('application_status_histories')->insertBatch($stageHistories);
                }
            }

            $database->table('applicant_assignment_histories')->insert([
                'applicant_id' => $applicantId,
                'from_hrd_team_id' => $assignedTeamId,
                'to_hrd_team_id' => null,
                'action' => 'unassigned',
                'notes' => 'Pilihan pelamar dibatalkan dari ' . $applicant['hrd_team_name'] . '.'
                    . ($talentPoolRemoved ? ' Data Talent Pool turut dihapus.' : '')
                    . ' Seluruh alur rekrutmen dikembalikan ke Lamaran Baru.',
                'changed_by' => $userId,
                'created_at' => $now,
            ]);
        }
        $database->transComplete();

        if (! $cancelled || ! $database->transStatus()) {
            return $this->candidateError('Pilihan pelamar gagal dibatalkan. Silakan muat ulang halaman.', $returnTeamId);
        }

        return redirect()->to($this->candidateUrl($returnTeamId))
            ->with(
                'candidate_success',
                $applicant['full_name'] . ' berhasil dikembalikan ke Belum dipilih.'
                    . ($talentPoolRemoved ? ' Data Talent Pool juga telah dihapus.' : '')
                    . ' Seluruh alur rekrutmen kembali ke Lamaran Baru.'
            );
    }

    public function updateStage(int $applicationId): RedirectResponse
    {
        $database = db_connect();
        $application = $database->table('applications AS applications')
            ->select('applications.*, applicants.assigned_hrd_team_id AS owner_hrd_team_id, vacancies.recruitment_process_template_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->where('applications.id', $applicationId)
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null)
            ->get()->getRowArray();
        $userId = (int) (session()->get('hrd_auth')['user_id'] ?? 0);
        $currentTeam = $this->currentTeam($userId);
        $canManageTeams = Services::authorization()->can($userId, 'hrd.teams.manage');
        $returnTeamId = max(0, (int) $this->request->getPost('team_id'));
        $newStage = trim((string) $this->request->getPost('stage'));
        if ($application === null) {
            return $this->candidateError('Lamaran atau tahapan yang dipilih tidak valid.', $returnTeamId);
        }
        if (Services::applicantBlacklist()->isActive((int) $application['applicant_id'])) {
            return $this->candidateError('Pelamar berada dalam blacklist aktif dan tahap seleksinya tidak dapat diubah.', $returnTeamId);
        }
        $allStages = $database->table('recruitment_stages')->where('is_active', 1)->orderBy('display_order')->get()->getResultArray();
        $templateStages = $this->templateStages();
        $allowedStages = $this->nextStages((int) $application['recruitment_process_template_id'], (string) $application['application_status'], $templateStages, $allStages);
        $stage = null;
        foreach ($allowedStages as $allowedStage) {
            if ((string) $allowedStage['code'] === $newStage) {
                $stage = $allowedStage;
                break;
            }
        }
        if ($stage === null) {
            return $this->candidateError('Tahapan tidak sesuai urutan template lowongan. Pilih tahap berikutnya yang tersedia.', $returnTeamId);
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

        $internalNotes = mb_substr(trim((string) $this->request->getPost('notes')), 0, 2000);
        $historyNotes = $internalNotes;
        $rejectionTemplate = null;
        $rejectionSnapshot = null;
        $isRejectionDecision = in_array($newStage, ['rejected', 'screening_failed'], true);
        $publicMessage = 'Lamaran Anda saat ini berada pada tahap ' . $stage['name'] . '.';
        if ($newStage === 'document_screening') {
            $publicMessage = 'Lamaran Anda sedang diperiksa oleh tim HRD.';
        } elseif ($newStage === 'screening_passed') {
            $publicMessage = 'Lamaran Anda dinyatakan lolos screening oleh tim HRD.';
        } elseif ($isRejectionDecision) {
            $templateId = (int) $this->request->getPost('rejection_template_id');
            $rejectionTemplate = $database->table('rejection_reason_templates')->where('id', $templateId)->where('is_active', 1)->get()->getRowArray();
            if ($rejectionTemplate === null) {
                return $this->candidateError('Pilih alasan gugur terlebih dahulu.', $returnTeamId);
            }
            $rejectionSnapshot = $this->rejectionStageSnapshot(
                (int) $application['recruitment_process_template_id'],
                (string) $application['application_status'],
                $templateStages,
                $allStages
            );
            $publicMessage = (string) $rejectionTemplate['reason_text'];
            $historyNotes = 'Gugur di ' . $this->rejectionStageLabel($rejectionSnapshot) . '. Alasan: ' . $rejectionTemplate['title']
                . ($internalNotes !== '' ? '. Catatan: ' . $internalNotes : '');
        } elseif ($newStage === 'accepted') {
            $publicMessage = 'Selamat, lamaran Anda dinyatakan diterima. Tim HRD akan menghubungi Anda untuk proses berikutnya.';
        }

        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('applications')->where('id', $applicationId)->update([
            'application_status' => $newStage,
            'screening_status' => $newStage === 'screening_passed' ? 'passed' : ($newStage === 'screening_failed' ? 'failed' : (string) $application['screening_status']),
            'screening_notes' => in_array($newStage, ['screening_passed', 'screening_failed'], true) ? ($historyNotes !== '' ? $historyNotes : 'Screening diputuskan secara manual oleh HRD.') : $application['screening_notes'],
            'public_message' => mb_substr($publicMessage, 0, 500),
            'reviewed_at' => $now,
            'reviewed_by' => $userId,
            'updated_at' => $now,
        ]);
        if ($isRejectionDecision && $rejectionTemplate !== null && $rejectionSnapshot !== null) {
            $rejectionData = [
                'stage_code' => (string) $rejectionSnapshot['code'],
                'stage_name_snapshot' => (string) $rejectionSnapshot['name'],
                'stage_order_snapshot' => $rejectionSnapshot['display_order'] !== null ? (int) $rejectionSnapshot['display_order'] : null,
                'reason_template_id' => (int) $rejectionTemplate['id'],
                'reason_title_snapshot' => (string) $rejectionTemplate['title'],
                'reason_text_snapshot' => (string) $rejectionTemplate['reason_text'],
                'internal_notes' => $internalNotes !== '' ? $internalNotes : null,
                'rejected_by' => $userId,
                'rejected_at' => $now,
                'updated_at' => $now,
            ];
            $existingRejection = $database->table('application_rejections')->where('application_id', $applicationId)->countAllResults() > 0;
            if ($existingRejection) {
                $database->table('application_rejections')->where('application_id', $applicationId)->update($rejectionData);
            } else {
                $database->table('application_rejections')->insert(['application_id' => $applicationId, 'created_at' => $now] + $rejectionData);
            }
        }
        $database->table('application_status_histories')->insert([
            'application_id' => $applicationId,
            'status_type' => 'application',
            'previous_status' => (string) $application['application_status'],
            'new_status' => $newStage,
            'notes' => $historyNotes !== '' ? $historyNotes : 'Tahapan kandidat diubah menjadi ' . $stage['name'] . '.',
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
            ->select('applications.id, applications.applicant_id, applications.vacancy_id, applications.application_number, applications.screening_status, applications.screening_score, applications.application_status, applicants.assigned_hrd_team_id, applicants.assigned_at, applications.submitted_at, applications.updated_at, applicants.full_name, applicants.email, applicants.phone, applicants.birth_date, vacancies.title AS vacancy_title, vacancies.department_id, vacancies.recruitment_process_template_id, process_templates.name AS process_template_name, periods.period_name, departments.name AS department_name, teams.name AS hrd_team_name, assigned_user.full_name AS assigned_by_name, stages.sla_days, stages.color_hex, rejections.stage_code AS rejected_stage_code, rejections.stage_name_snapshot AS rejected_stage_name, rejections.stage_order_snapshot AS rejected_stage_order, rejections.reason_title_snapshot AS rejection_reason_title, rejections.rejected_at, talent_pool.id AS talent_pool_id, active_blacklist.id AS active_blacklist_id, (SELECT MAX(histories.created_at) FROM application_status_histories histories WHERE histories.application_id = applications.id) AS stage_changed_at', false)
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('recruitment_process_templates AS process_templates', 'process_templates.id = vacancies.recruitment_process_template_id', 'left')
            ->join('vacancy_recruitment_periods AS periods', 'periods.id = applications.vacancy_period_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id')
            ->join('users AS assigned_user', 'assigned_user.id = applicants.assigned_by_user_id', 'left')
            ->join('recruitment_stages AS stages', 'stages.code = applications.application_status', 'left')
            ->join('application_rejections AS rejections', 'rejections.application_id = applications.id', 'left')
            ->join('talent_pool_candidates AS talent_pool', 'talent_pool.applicant_id = applications.applicant_id', 'left')
            ->join('applicant_blacklists AS active_blacklist', 'active_blacklist.applicant_id = applicants.id AND active_blacklist.revoked_at IS NULL AND active_blacklist.starts_at <= NOW() AND (active_blacklist.is_permanent = 1 OR active_blacklist.ends_at >= NOW())', 'left', false)
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null);
    }

    /** @return array<int, list<array<string, mixed>>> */
    private function templateStages(): array
    {
        $rows = db_connect()->table('recruitment_process_template_stages AS links')
            ->select('links.template_id, links.display_order, stages.id, stages.code, stages.name, stages.color_hex, stages.sla_days, stages.is_terminal')
            ->join('recruitment_stages AS stages', 'stages.id = links.stage_id')
            ->where('stages.is_active', 1)
            ->orderBy('links.template_id')->orderBy('links.display_order')->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['template_id']][] = $row;
        }
        return $result;
    }

    /**
     * @param array<int, list<array<string, mixed>>> $templateStages
     * @param list<array<string, mixed>> $allStages
     * @return list<array<string, mixed>>
     */
    private function nextStages(int $templateId, string $currentStatus, array $templateStages, array $allStages): array
    {
        if (in_array($currentStatus, ['accepted', 'hired', 'rejected', 'withdrawn'], true)) {
            return [];
        }
        $sequence = $templateStages[$templateId] ?? [];

        if ($currentStatus === 'screening_failed') {
            return [
                $this->manualScreeningStage('screening_passed', 'Ubah menjadi Lolos Screening', '#16A34A'),
            ];
        }

        if ($currentStatus === 'lamaran_baru') {
            foreach ($sequence as $stage) {
                if ((string) $stage['code'] === 'document_screening') {
                    $stage['name'] = 'Mulai Screening';
                    return [$stage];
                }
            }
        }

        if ($currentStatus === 'document_screening') {
            return [
                $this->manualScreeningStage('screening_passed', 'Lolos Screening', '#16A34A'),
                $this->manualScreeningStage('screening_failed', 'Tidak Lolos Screening', '#DC2626', true),
            ];
        }

        if ($currentStatus === 'screening_passed') {
            foreach ($sequence as $index => $stage) {
                if ((string) $stage['code'] === 'document_screening') {
                    $available = isset($sequence[$index + 1]) ? [$sequence[$index + 1]] : [];
                    return $this->withRejectedStage($available, $allStages);
                }
            }
        }

        $currentIndex = null;
        foreach ($sequence as $index => $stage) {
            if ((string) $stage['code'] === $currentStatus || in_array($currentStatus, self::STATUS_ALIASES[(string) $stage['code']] ?? [], true)) {
                $currentIndex = $index;
                break;
            }
        }
        $available = [];
        $nextIndex = $currentIndex === null ? 0 : $currentIndex + 1;
        if (isset($sequence[$nextIndex])) {
            $available[] = $sequence[$nextIndex];
        }
        return $this->withRejectedStage($available, $allStages);
    }

    /** @return array<string, mixed> */
    private function manualScreeningStage(string $code, string $name, string $color, bool $terminal = false): array
    {
        return ['id' => 0, 'code' => $code, 'name' => $name, 'color_hex' => $color, 'sla_days' => 0, 'is_terminal' => $terminal ? 1 : 0];
    }

    /** @param list<array<string, mixed>> $available @param list<array<string, mixed>> $allStages @return list<array<string, mixed>> */
    private function withRejectedStage(array $available, array $allStages): array
    {
        foreach ($allStages as $stage) {
            if ((string) $stage['code'] === 'rejected') {
                $available[] = $stage;
                break;
            }
        }
        return $available;
    }

    /**
     * @param array<int, list<array<string, mixed>>> $templateStages
     * @return list<array<string, mixed>>
     */
    private function processSteps(int $templateId, string $currentStatus, array $templateStages): array
    {
        $sequence = $templateStages[$templateId] ?? [];
        if ($sequence === []) {
            return [];
        }

        $normalizedStatus = in_array($currentStatus, ['screening_passed', 'screening_failed'], true)
            ? 'document_screening'
            : $currentStatus;
        $currentIndex = null;
        foreach ($sequence as $index => $stage) {
            if ((string) $stage['code'] === $normalizedStatus || in_array($currentStatus, self::STATUS_ALIASES[(string) $stage['code']] ?? [], true)) {
                $currentIndex = $index;
                break;
            }
        }

        foreach ($sequence as $index => &$stage) {
            if ($currentIndex === null) {
                $stage['progress_state'] = $index === 0 ? 'next' : 'upcoming';
            } elseif ($index < $currentIndex) {
                $stage['progress_state'] = 'previous';
            } elseif ($index === $currentIndex) {
                $stage['progress_state'] = 'current';
            } elseif ($index === $currentIndex + 1) {
                $stage['progress_state'] = 'next';
            } else {
                $stage['progress_state'] = 'upcoming';
            }
        }
        unset($stage);

        return $sequence;
    }

    /** @param list<string> $statuses @param list<string> $rejectionStageCodes
     * @return array{keyword: string, age: int, vacancy_id: int, vacancy_period_id: int, department_id: int, status: string, rejection_stage_code: string}
     */
    private function filters(array $statuses, array $rejectionStageCodes): array
    {
        $status = trim((string) $this->request->getGet('status'));
        $rejectionStageCode = trim((string) $this->request->getGet('rejection_stage_code'));
        $age = (int) $this->request->getGet('age');

        return [
            'keyword' => mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100),
            'age' => $age >= 15 && $age <= 80 ? $age : 0,
            'vacancy_id' => max(0, (int) $this->request->getGet('vacancy_id')),
            'vacancy_period_id' => max(0, (int) $this->request->getGet('vacancy_period_id')),
            'department_id' => max(0, (int) $this->request->getGet('department_id')),
            'status' => in_array($status, $statuses, true) ? $status : '',
            'rejection_stage_code' => in_array($rejectionStageCode, $rejectionStageCodes, true) ? $rejectionStageCode : '',
        ];
    }

    /** @param array{keyword: string, age: int, vacancy_id: int, vacancy_period_id: int, department_id: int, status: string, rejection_stage_code: string} $filters */
    private function applyFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['keyword'] !== '') {
            $builder->groupStart()->like('applicants.full_name', $filters['keyword'])->orLike('applicants.email', $filters['keyword'])->orLike('applicants.phone', $filters['keyword'])->orLike('applications.application_number', $filters['keyword'])->groupEnd();
        }
        if ($filters['age'] > 0) {
            $today = new \DateTimeImmutable('today');
            $latestBirthDate = $today->modify('-' . $filters['age'] . ' years')->format('Y-m-d');
            $earliestBirthDate = $today->modify('-' . ($filters['age'] + 1) . ' years')->modify('+1 day')->format('Y-m-d');
            $builder->where('applicants.birth_date >=', $earliestBirthDate)
                ->where('applicants.birth_date <=', $latestBirthDate);
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
        if ($filters['rejection_stage_code'] !== '') {
            $builder->where('rejections.stage_code', $filters['rejection_stage_code']);
        }
    }

    /** @param array<int, list<array<string, mixed>>> $templateStages @param list<array<string, mixed>> $allStages @return array{code: string, name: string, display_order: int|null} */
    private function rejectionStageSnapshot(int $templateId, string $currentStatus, array $templateStages, array $allStages): array
    {
        $normalizedStatus = match ($currentStatus) {
            'screening_passed', 'screening_failed' => 'document_screening',
            default => $currentStatus,
        };
        foreach ($templateStages[$templateId] ?? [] as $stage) {
            if ((string) $stage['code'] === $normalizedStatus || in_array($currentStatus, self::STATUS_ALIASES[(string) $stage['code']] ?? [], true)) {
                return ['code' => (string) $stage['code'], 'name' => (string) $stage['name'], 'display_order' => (int) $stage['display_order']];
            }
        }
        foreach ($allStages as $stage) {
            if ((string) $stage['code'] === $normalizedStatus || in_array($currentStatus, self::STATUS_ALIASES[(string) $stage['code']] ?? [], true)) {
                return ['code' => (string) $stage['code'], 'name' => (string) $stage['name'], 'display_order' => null];
            }
        }

        return ['code' => $normalizedStatus !== '' ? $normalizedStatus : 'unknown', 'name' => $this->statusLabel($currentStatus, $allStages), 'display_order' => null];
    }

    /** @param array<string, mixed> $stage */
    private function rejectionStageLabel(array $stage): string
    {
        $order = isset($stage['display_order']) && $stage['display_order'] !== null ? (int) $stage['display_order'] : null;

        return ($order !== null ? 'Tahap ' . $order . ' — ' : '') . (string) ($stage['name'] ?? 'Tahap tidak tercatat');
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
