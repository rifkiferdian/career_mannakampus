<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use Config\Services;

class ApplicantDetailController extends BaseController
{
    public function show(int $applicantId): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $applicant = $database->table('applicants')
            ->select('id, full_name, email, phone, profile_photo_path, birth_place, birth_date, height_cm, gender, marital_status, religion, address, last_education, institution, major, gpa, training_experience, is_active, created_at, updated_at')
            ->where('id', $applicantId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();
        if ($applicant === null) {
            throw PageNotFoundException::forPageNotFound('Pelamar tidak ditemukan.');
        }

        $applications = $database->table('applications AS applications')
            ->select('applications.*, vacancies.title AS vacancy_title, departments.name AS department_name, batches.batch_number, rejections.stage_code AS rejected_stage_code, rejections.stage_name_snapshot AS rejected_stage_name, rejections.stage_order_snapshot AS rejected_stage_order, rejections.reason_title_snapshot AS rejection_reason_title, rejections.reason_text_snapshot AS rejection_reason_text, rejections.internal_notes AS rejection_internal_notes, rejections.rejected_at, rejected_user.full_name AS rejected_by_name')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('application_batches AS batches', 'batches.id = applications.batch_id')
            ->join('application_rejections AS rejections', 'rejections.application_id = applications.id', 'left')
            ->join('users AS rejected_user', 'rejected_user.id = rejections.rejected_by', 'left')
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
                ->select('histories.application_id, histories.status_type, histories.previous_status, histories.new_status, histories.notes, histories.created_at, users.full_name AS changed_by_name')
                ->join('users', 'users.id = histories.changed_by', 'left')
                ->whereIn('histories.application_id', $applicationIds)
                ->orderBy('histories.created_at', 'DESC')
                ->get()
                ->getResultArray());
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

        return view('admin/applicant_detail', [
            'auth' => $auth,
            'applicant' => $applicant,
            'applications' => $applications,
            'answersByApplication' => $answers,
            'historiesByApplication' => $histories,
            'workExperiencesByBatch' => $workExperiencesByBatch,
            'documents' => $documents,
            'canDownloadDocuments' => Services::authorization()->can($userId, 'candidates.cv.download'),
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

        $filename = trim((string) $document['original_name']);
        $filename = preg_replace('~[\x00-\x1F\x7F"/\\\\]+~u', '-', $filename) ?: 'dokumen-pelamar.pdf';
        $response = $this->response->download($path, null, true);
        if (! $response instanceof DownloadResponse) {
            throw PageNotFoundException::forPageNotFound('File dokumen tidak dapat diunduh.');
        }

        return $response->setFileName($filename);
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

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
