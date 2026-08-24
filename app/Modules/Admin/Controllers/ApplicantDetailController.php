<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Recruitment\Services\ApplicantBlacklistService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use Config\Services;

class ApplicantDetailController extends BaseController
{
    /** @var array<string, list<string>> */
    private const STATUS_ALIASES = [
        'under_review' => ['under_review', 'reviewed'],
        'hrd_interview' => ['hrd_interview', 'interview_hr', 'interview_scheduled'],
        'user_interview' => ['user_interview', 'interview_user'],
        'accepted' => ['accepted', 'hired'],
    ];

    public function show(int $applicantId): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $applicant = $database->table('applicants AS applicants')
            ->select('applicants.id, applicants.full_name, applicants.email, applicants.phone, applicants.profile_photo_path, applicants.birth_place, applicants.birth_date, applicants.height_cm, applicants.gender, applicants.marital_status, applicants.religion, applicants.address, applicants.last_education, applicants.institution, applicants.major, applicants.gpa, applicants.training_experience, applicants.is_active, applicants.assigned_hrd_team_id, applicants.created_at, applicants.updated_at, teams.name AS assigned_hrd_team_name')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id', 'left')
            ->where('applicants.id', $applicantId)
            ->where('applicants.deleted_at', null)
            ->get()
            ->getRowArray();
        if ($applicant === null) {
            throw PageNotFoundException::forPageNotFound('Pelamar tidak ditemukan.');
        }

        $applications = $database->table('applications AS applications')
            ->select('applications.*, vacancies.title AS vacancy_title, vacancies.department_id, vacancies.recruitment_process_template_id, departments.name AS department_name, batches.batch_number, rejections.stage_code AS rejected_stage_code, rejections.stage_name_snapshot AS rejected_stage_name, rejections.stage_order_snapshot AS rejected_stage_order, rejections.reason_title_snapshot AS rejection_reason_title, rejections.reason_text_snapshot AS rejection_reason_text, rejections.internal_notes AS rejection_internal_notes, rejections.rejected_at, rejected_user.full_name AS rejected_by_name, talent_pool.id AS talent_pool_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('application_batches AS batches', 'batches.id = applications.batch_id')
            ->join('application_rejections AS rejections', "rejections.application_id = applications.id AND applications.application_status IN ('rejected', 'screening_failed')", 'left', false)
            ->join('users AS rejected_user', 'rejected_user.id = rejections.rejected_by', 'left')
            ->join('talent_pool_candidates AS talent_pool', 'talent_pool.applicant_id = applications.applicant_id', 'left')
            ->where('applications.applicant_id', $applicantId)
            ->where('applications.deleted_at', null)
            ->orderBy('applications.submitted_at', 'DESC')
            ->orderBy('applications.preference_order', 'ASC')
            ->get()
            ->getResultArray();
        $applicationIds = array_map('intval', array_column($applications, 'id'));
        $batchIds = array_values(array_unique(array_map('intval', array_column($applications, 'batch_id'))));
        $answers = [];
        $histories = [];
        $schedules = [];
        $workExperiencesByBatch = [];
        if ($applicationIds !== []) {
            $answers = $this->groupByApplication($database->table('application_screening_answers AS answers')
                ->select('answers.application_id, answers.answer_value, answers.is_eligible, answers.score, questions.question_text, questions.answer_type, questions.is_knockout')
                ->join('vacancy_screening_questions AS questions', 'questions.id = answers.question_id')
                ->whereIn('answers.application_id', $applicationIds)
                ->orderBy('questions.display_order', 'ASC')
                ->get()
                ->getResultArray());
            $histories = $this->groupByApplication($database->table('application_status_histories AS histories')
                ->select('histories.id, histories.application_id, histories.status_type, histories.previous_status, histories.new_status, histories.notes, histories.changed_by, histories.created_at, users.full_name AS changed_by_name')
                ->join('users', 'users.id = histories.changed_by', 'left')
                ->whereIn('histories.application_id', $applicationIds)
                ->orderBy('histories.created_at', 'DESC')->orderBy('histories.id', 'DESC')
                ->get()
                ->getResultArray());
            $schedules = $this->groupByApplication($database->table('recruitment_schedules AS schedules')
                ->select('schedules.*, stages.name AS stage_name, pic.full_name AS pic_name')
                ->join('recruitment_stages AS stages', 'stages.id = schedules.stage_id')
                ->join('users AS pic', 'pic.id = schedules.pic_user_id')
                ->whereIn('schedules.application_id', $applicationIds)
                ->orderBy('schedules.created_at', 'DESC')
                ->get()->getResultArray());
        }
        if ($batchIds !== []) {
            foreach ($database->table('application_work_experiences')
                ->whereIn('batch_id', $batchIds)
                ->orderBy('display_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()->getResultArray() as $experience) {
                $workExperiencesByBatch[(int) $experience['batch_id']][] = $experience;
            }
        }
        $documents = $database->table('applicant_documents AS documents')
            ->select('documents.id, documents.batch_id, documents.document_type, documents.original_name, documents.mime_type, documents.file_size, documents.created_at, batches.batch_number')
            ->join('application_batches AS batches', 'batches.id = documents.batch_id')
            ->where('documents.applicant_id', $applicantId)
            ->orderBy('documents.created_at', 'DESC')
            ->get()
            ->getResultArray();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $currentTeam = $database->table('hrd_team_users AS team_users')
            ->select('teams.id, teams.name')
            ->join('hrd_teams AS teams', 'teams.id = team_users.hrd_team_id')
            ->where('team_users.user_id', $userId)
            ->where('teams.is_active', 1)
            ->get()->getRowArray();
        $assignedTeamId = (int) ($applicant['assigned_hrd_team_id'] ?? 0);
        $canManageAssignedTeam = $assignedTeamId > 0
            && (Services::authorization()->can($userId, 'hrd.teams.manage') || (int) ($currentTeam['id'] ?? 0) === $assignedTeamId);
        $canManageAllTeams = Services::authorization()->can($userId, 'hrd.teams.manage');
        $canUpdateStatus = Services::authorization()->can($userId, 'candidates.status.update') && $canManageAssignedTeam;
        $stageRows = $database->table('recruitment_process_template_stages AS links')
            ->select('links.template_id, links.display_order, stages.id, stages.code, stages.name, stages.color_hex')
            ->join('recruitment_stages AS stages', 'stages.id = links.stage_id')
            ->where('stages.is_active', 1)->orderBy('links.template_id')->orderBy('links.display_order')->get()->getResultArray();
        $templateStages = [];
        foreach ($stageRows as $stageRow) {
            $templateStages[(int) $stageRow['template_id']][] = $stageRow;
        }
        $stageIdsByCode = array_column($database->table('recruitment_stages')->select('id, code')->get()->getResultArray(), 'id', 'code');
        foreach ($applications as &$application) {
            $applicationId = (int) $application['id'];
            $application['process_steps'] = $this->processSteps(
                (int) $application['recruitment_process_template_id'],
                (string) $application['application_status'],
                $templateStages
            );
            $lastChange = $histories[$applicationId][0] ?? null;
            $blockedBySchedule = false;
            $currentStageId = (int) ($stageIdsByCode[(string) $application['application_status']] ?? 0);
            foreach ($schedules[$applicationId] ?? [] as $schedule) {
                if ((int) $schedule['stage_id'] === $currentStageId
                    && in_array((string) $schedule['status'], ['confirmed', 'reschedule_requested', 'present', 'absent'], true)) {
                    $blockedBySchedule = true;
                    break;
                }
            }
            $application['last_stage_change'] = $lastChange;
            $application['can_undo_stage'] = $canUpdateStatus
                && is_array($lastChange)
                && (string) $lastChange['status_type'] === 'application'
                && (string) $lastChange['new_status'] === (string) $application['application_status']
                && trim((string) $lastChange['previous_status']) !== ''
                && (string) $lastChange['previous_status'] !== (string) $lastChange['new_status']
                && ($canManageAllTeams || (int) $lastChange['changed_by'] === $userId)
                && ! $blockedBySchedule;
        }
        unset($application);
        $canViewBlacklist = Services::authorization()->can($userId, 'applicants.blacklist.view');
        $blacklist = null;
        $blacklistHistories = [];
        if ($canViewBlacklist) {
            $blacklist = $database->table('applicant_blacklists AS blacklists')
                ->select('blacklists.*, creator.full_name AS created_by_name, updater.full_name AS updated_by_name, revoker.full_name AS revoked_by_name')
                ->join('users AS creator', 'creator.id = blacklists.created_by', 'left')
                ->join('users AS updater', 'updater.id = blacklists.updated_by', 'left')
                ->join('users AS revoker', 'revoker.id = blacklists.revoked_by', 'left')
                ->where('blacklists.applicant_id', $applicantId)
                ->get()->getRowArray() ?: null;
            if ($blacklist !== null) {
                $blacklist['computed_status'] = ApplicantBlacklistService::statusOf($blacklist);
                $blacklistHistories = $database->table('applicant_blacklist_histories AS histories')
                    ->select('histories.*, users.full_name AS changed_by_name')
                    ->join('users', 'users.id = histories.changed_by', 'left')
                    ->where('histories.blacklist_id', (int) $blacklist['id'])
                    ->orderBy('histories.created_at', 'DESC')
                    ->orderBy('histories.id', 'DESC')
                    ->get()->getResultArray();
            }
        }

        return view('admin/applicant_detail', [
            'auth' => $auth,
            'applicant' => $applicant,
            'applications' => $applications,
            'answersByApplication' => $answers,
            'historiesByApplication' => $histories,
            'schedulesByApplication' => $schedules,
            'workExperiencesByBatch' => $workExperiencesByBatch,
            'documents' => $documents,
            'canDownloadDocuments' => Services::authorization()->can($userId, 'candidates.cv.download'),
            'canViewBlacklist' => $canViewBlacklist,
            'canManageBlacklist' => Services::authorization()->can($userId, 'applicants.blacklist.manage'),
            'canSaveTalentPool' => Services::authorization()->can($userId, 'candidates.status.update') && $canManageAssignedTeam,
            'canCancelAssignment' => Services::authorization()->can($userId, 'applicants.assign') && $canManageAssignedTeam,
            'departments' => $database->table('departments')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'blacklist' => $blacklist,
            'blacklistHistories' => $blacklistHistories,
            'blacklistSuccess' => session()->getFlashdata('blacklist_success'),
            'blacklistError' => session()->getFlashdata('blacklist_error'),
            'candidateSuccess' => session()->getFlashdata('candidate_success'),
            'candidateError' => session()->getFlashdata('candidate_error'),
        ]);
    }

    public function downloadDocument(int $applicantId, int $documentId): DownloadResponse
    {
        $document = db_connect()->table('applicant_documents')
            ->where('id', $documentId)
            ->where('applicant_id', $applicantId)
            ->get()
            ->getRowArray();
        if ($document === null) {
            throw PageNotFoundException::forPageNotFound('Dokumen tidak ditemukan.');
        }

        $uploadRoot = realpath(WRITEPATH . 'uploads');
        $path = $uploadRoot === false ? false : realpath($uploadRoot . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $document['file_path']), DIRECTORY_SEPARATOR));
        if ($path === false || $uploadRoot === false || ! is_file($path) || ! str_starts_with(mb_strtolower($path), mb_strtolower($uploadRoot . DIRECTORY_SEPARATOR))) {
            throw PageNotFoundException::forPageNotFound('File dokumen tidak ditemukan.');
        }

        $extension = mb_strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'pdf';
        $objectName = substr(hash('sha256', $documentId . ':' . $document['file_path']), 0, 12) . '.' . $extension;
        $response = $this->response->download($path, null, true);
        if (! $response instanceof DownloadResponse) {
            throw PageNotFoundException::forPageNotFound('File dokumen tidak dapat ditampilkan.');
        }

        return $response
            ->setFileName($objectName)
            ->setHeader('Content-Disposition', 'inline; filename="' . $objectName . '"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0');
    }

    /** @param list<array<string, mixed>> $rows
     * @return array<int, list<array<string, mixed>>>
     */
    private function groupByApplication(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['application_id']][] = $row;
        }

        return $grouped;
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
            if ((string) $stage['code'] === $normalizedStatus
                || in_array($currentStatus, self::STATUS_ALIASES[(string) $stage['code']] ?? [], true)) {
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

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
