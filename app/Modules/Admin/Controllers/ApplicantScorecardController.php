<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Services\CandidateScorecardPdf;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use Config\Services;

class ApplicantScorecardController extends BaseController
{
    public function download(int $applicantId): DownloadResponse
    {
        $this->response->setHeader('Cache-Control', 'private, no-store, max-age=0')->setHeader('Pragma', 'no-cache');
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        if (! Services::authorization()->can($userId, 'recommendations.view')) {
            throw PageNotFoundException::forPageNotFound('Scorecard kandidat tidak ditemukan.');
        }

        $database = db_connect();
        $applicant = $database->table('applicants AS applicants')
            ->select('applicants.id, applicants.full_name, applicants.email, applicants.phone, applicants.birth_place, applicants.birth_date, applicants.height_cm, applicants.gender, applicants.marital_status, applicants.religion, applicants.address, applicants.last_education, applicants.institution, applicants.major, applicants.gpa, applicants.training_experience, applicants.assigned_hrd_team_id, applicants.created_at, teams.name AS assigned_hrd_team_name')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id', 'left')
            ->where('applicants.id', $applicantId)->where('applicants.deleted_at', null)
            ->get()->getRowArray();
        if ($applicant === null) {
            throw PageNotFoundException::forPageNotFound('Pelamar tidak ditemukan.');
        }

        $applications = $database->table('applications AS applications')
            ->select('applications.id, applications.batch_id, applications.application_number, applications.application_status, applications.screening_status, applications.screening_score, applications.screening_notes, applications.work_experience, applications.work_motivation, applications.career_goal, applications.submitted_at, vacancies.title AS vacancy_title, departments.name AS department_name, periods.period_name')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('vacancy_recruitment_periods AS periods', 'periods.id = applications.vacancy_period_id')
            ->where('applications.applicant_id', $applicantId)->where('applications.deleted_at', null)
            ->orderBy('applications.submitted_at', 'DESC')->orderBy('applications.preference_order', 'ASC')
            ->get()->getResultArray();
        $statusLabels = ['lamaran_baru' => 'Lamaran Baru', 'submitted' => 'Lamaran diterima', 'screening_passed' => 'Lolos screening', 'screening_failed' => 'Tidak lolos screening', 'withdrawn' => 'Dibatalkan', 'accepted' => 'Diterima', 'hired' => 'Diterima', 'rejected' => 'Ditolak'];
        foreach ($database->table('recruitment_stages')->select('code, name')->get()->getResultArray() as $stage) {
            $statusLabels[(string) $stage['code']] = (string) $stage['name'];
        }
        foreach ($applications as &$application) {
            $code = (string) $application['application_status'];
            $application['status_label'] = $statusLabels[$code] ?? ucwords(str_replace('_', ' ', $code));
            $application['screening_label'] = ['pending' => 'Menunggu', 'passed' => 'Lolos', 'failed' => 'Tidak lolos', 'manual_review' => 'Tinjauan manual'][(string) $application['screening_status']] ?? ucwords(str_replace('_', ' ', (string) $application['screening_status']));
        }
        unset($application);

        $applicationIds = array_map('intval', array_column($applications, 'id'));
        $answersByApplication = [];
        if ($applicationIds !== []) {
            $answerRows = $database->table('application_screening_answers AS answers')
                ->select('answers.application_id, answers.answer_value, questions.question_text, questions.answer_type')
                ->join('vacancy_screening_questions AS questions', 'questions.id = answers.question_id')
                ->whereIn('answers.application_id', $applicationIds)
                ->orderBy('questions.display_order', 'ASC')->orderBy('answers.id', 'ASC')
                ->get()->getResultArray();
            foreach ($answerRows as $answer) {
                $answersByApplication[(int) $answer['application_id']][] = $answer;
            }
        }

        $batchIds = array_values(array_unique(array_map('intval', array_column($applications, 'batch_id'))));
        $experiences = $batchIds === [] ? [] : $database->table('application_work_experiences')
            ->whereIn('batch_id', $batchIds)->orderBy('display_order', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $recommendation = $database->table('applicant_recommendations AS recommendations')
            ->select('recommendations.*, users.full_name AS updated_by_name')
            ->join('users', 'users.id = recommendations.updated_by', 'left')
            ->where('recommendations.applicant_id', $applicantId)
            ->get()->getRowArray() ?: null;
        $aspects = $database->table('recommendation_aspects AS aspects')
            ->select('aspects.id, aspects.name, aspects.description, aspects.input_type, aspects.display_order, answers.answer_value')
            ->join('applicant_recommendation_answers AS answers', 'answers.aspect_id = aspects.id' . ($recommendation !== null ? ' AND answers.recommendation_id = ' . (int) $recommendation['id'] : ' AND 1 = 0'), 'left', false)
            ->groupStart()
                ->groupStart()->where('aspects.deleted_at', null)->where('aspects.is_active', 1)->groupEnd()
                ->orWhere('answers.id IS NOT NULL', null, false)
            ->groupEnd()
            ->orderBy('aspects.display_order', 'ASC')->orderBy('aspects.id', 'ASC')
            ->get()->getResultArray();

        $pdf = (new CandidateScorecardPdf())->generate([
            'applicant' => $applicant, 'applications' => $applications, 'experiences' => $experiences,
            'answers_by_application' => $answersByApplication, 'recommendation' => $recommendation, 'aspects' => $aspects,
            'printed_by' => (string) ($auth['name'] ?? 'Admin HRD'), 'printed_at' => date('d/m/Y H:i'),
            'logo_path' => FCPATH . 'assets/img/Logo_Manna_Kampus.png',
        ]);
        $safeName = trim((string) preg_replace('/[^a-z0-9]+/i', '-', mb_strtolower((string) $applicant['full_name'])), '-');
        $fileName = 'scorecard-' . ($safeName !== '' ? $safeName : 'kandidat') . '-' . date('Ymd-His') . '.pdf';
        $response = $this->response->download($fileName, $pdf, true);
        if (! $response instanceof DownloadResponse) {
            throw PageNotFoundException::forPageNotFound('PDF scorecard tidak dapat dibuat.');
        }

        return $response->setFileName($fileName)
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0');
    }
}
