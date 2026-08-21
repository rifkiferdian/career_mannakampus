<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class ScreeningQuestionController extends BaseController
{
    private const ANSWER_TYPES = ['text', 'number', 'boolean', 'yes_no', 'choice', 'education_level'];
    private const OPERATORS = ['', 'equals', 'between', 'greater_than_or_equal', 'minimum_education'];

    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $vacancies = $database->table('vacancies AS vacancies')
            ->select('vacancies.id, vacancies.title, vacancies.code, vacancies.status, departments.name AS department_name')
            ->join('departments', 'departments.id = vacancies.department_id', 'left')
            ->where('vacancies.deleted_at', null)
            ->orderBy('vacancies.title', 'ASC')
            ->get()->getResultArray();
        $selectedVacancyId = max(0, (int) $this->request->getGet('vacancy_id'));
        $selectedVacancy = null;
        foreach ($vacancies as $vacancy) {
            if ((int) $vacancy['id'] === $selectedVacancyId) {
                $selectedVacancy = $vacancy;
                break;
            }
        }
        if ($selectedVacancy === null) {
            $selectedVacancyId = 0;
        }

        $questions = [];
        if ($selectedVacancyId > 0) {
            $questions = $database->table('vacancy_screening_questions AS questions')
                ->select('questions.*, defaults.question_text AS source_question_text')
                ->join('default_screening_questions AS defaults', 'defaults.id = questions.source_default_question_id', 'left')
                ->where('questions.vacancy_id', $selectedVacancyId)
                ->orderBy('questions.display_order', 'ASC')
                ->orderBy('questions.id', 'ASC')
                ->get()->getResultArray();
        }

        $defaultQuestions = $database->table('default_screening_questions')
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        $existingDefaultIds = array_map('intval', array_filter(array_column($questions, 'source_default_question_id')));
        $existingQuestionCodes = array_column($questions, 'question_code');
        $copyableDefaultQuestions = array_map(
            static function (array $question) use ($existingDefaultIds, $existingQuestionCodes): array {
                $question['is_copyable'] = (int) $question['is_active'] === 1
                    && ! in_array((int) $question['id'], $existingDefaultIds, true)
                    && ! in_array($question['question_code'], $existingQuestionCodes, true);

                return $question;
            },
            array_values(array_filter($defaultQuestions, static fn (array $question): bool => (int) $question['is_active'] === 1)),
        );

        return view('admin/screening_questions', [
            'auth' => $auth,
            'defaultQuestions' => $defaultQuestions,
            'copyableDefaultQuestions' => $copyableDefaultQuestions,
            'vacancies' => $vacancies,
            'selectedVacancyId' => $selectedVacancyId,
            'selectedVacancy' => $selectedVacancy,
            'vacancyQuestions' => $questions,
            'canManageDefaults' => Services::authorization()->can($userId, 'screening.defaults.manage'),
            'canManageVacancyQuestions' => Services::authorization()->can($userId, 'screening.vacancies.manage'),
            'success' => session()->getFlashdata('screening_success'),
            'error' => session()->getFlashdata('screening_error'),
            'openModal' => (string) (session()->getFlashdata('screening_form') ?? ''),
        ]);
    }

    public function createDefault(): RedirectResponse
    {
        return $this->saveDefault(null);
    }

    public function updateDefault(int $questionId): RedirectResponse
    {
        return $this->saveDefault($questionId);
    }

    public function deleteDefault(int $questionId): RedirectResponse
    {
        $question = db_connect()->table('default_screening_questions')->where('id', $questionId)->get()->getRowArray();
        if ($question === null) {
            return $this->error('Pertanyaan default tidak ditemukan.', '#default-questions');
        }
        db_connect()->table('default_screening_questions')->where('id', $questionId)->delete();

        return $this->success('Pertanyaan default berhasil dihapus.', '#default-questions');
    }

    public function copyDefaults(int $vacancyId): RedirectResponse
    {
        if ($this->findVacancy($vacancyId) === null) {
            return $this->error('Lowongan tidak ditemukan.', '#vacancy-questions');
        }

        $submittedIds = $this->request->getPost('default_question_ids');
        $selectedIds = is_array($submittedIds)
            ? array_values(array_unique(array_filter(array_map('intval', $submittedIds), static fn (int $id): bool => $id > 0)))
            : [];
        if ($selectedIds === []) {
            return $this->copyError($vacancyId, 'Pilih minimal satu pertanyaan default yang akan disalin.');
        }

        $database = db_connect();
        $existing = $database->table('vacancy_screening_questions')
            ->select('question_code, source_default_question_id')
            ->where('vacancy_id', $vacancyId)->get()->getResultArray();
        $existingCodes = array_column($existing, 'question_code');
        $existingSources = array_map('intval', array_filter(array_column($existing, 'source_default_question_id')));
        $added = 0;
        $now = date('Y-m-d H:i:s');

        $selectedQuestions = $database->table('default_screening_questions')
            ->where('is_active', 1)
            ->whereIn('id', $selectedIds)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        if ($selectedQuestions === []) {
            return $this->copyError($vacancyId, 'Pertanyaan default yang dipilih tidak tersedia atau sudah nonaktif.');
        }

        foreach ($selectedQuestions as $question) {
            if (in_array((int) $question['id'], $existingSources, true) || in_array($question['question_code'], $existingCodes, true)) {
                continue;
            }
            $database->table('vacancy_screening_questions')->insert([
                'vacancy_id' => $vacancyId,
                'source_default_question_id' => $question['id'],
                'question_code' => $question['question_code'],
                'question_text' => $question['question_text'],
                'answer_type' => $question['answer_type'],
                'answer_options' => $question['answer_options'],
                'is_required' => $question['is_required'],
                'is_knockout' => $question['is_knockout'],
                'expected_value' => $question['expected_value'],
                'comparison_operator' => $question['comparison_operator'],
                'display_order' => $question['display_order'],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $added++;
        }

        if ($added === 0) {
            return $this->copyError($vacancyId, 'Pertanyaan yang dipilih sudah ada pada lowongan ini.');
        }

        return $this->success($added . ' pertanyaan default terpilih berhasil disalin.', $this->vacancyAnchor($vacancyId));
    }

    public function createVacancyQuestion(int $vacancyId): RedirectResponse
    {
        return $this->saveVacancyQuestion($vacancyId, null);
    }

    public function updateVacancyQuestion(int $vacancyId, int $questionId): RedirectResponse
    {
        return $this->saveVacancyQuestion($vacancyId, $questionId);
    }

    public function deleteVacancyQuestion(int $vacancyId, int $questionId): RedirectResponse
    {
        $question = db_connect()->table('vacancy_screening_questions')->where('id', $questionId)->where('vacancy_id', $vacancyId)->get()->getRowArray();
        if ($question === null) {
            return $this->error('Pertanyaan screening lowongan tidak ditemukan.', $this->vacancyAnchor($vacancyId));
        }
        db_connect()->table('vacancy_screening_questions')->where('id', $questionId)->delete();

        return $this->success('Pertanyaan screening lowongan berhasil dihapus.', $this->vacancyAnchor($vacancyId));
    }

    private function saveDefault(?int $questionId): RedirectResponse
    {
        $existing = $questionId === null ? null : db_connect()->table('default_screening_questions')->where('id', $questionId)->get()->getRowArray();
        if ($questionId !== null && $existing === null) {
            return $this->error('Pertanyaan default tidak ditemukan.', '#default-questions');
        }
        $data = $this->validatedInput();
        if (is_string($data)) {
            return $this->formError($data, '#default-questions');
        }
        if ($questionId === null) {
            $data['question_code'] = $this->uniqueDefaultCode((string) $data['question_text']);
            $data['created_at'] = date('Y-m-d H:i:s');
            db_connect()->table('default_screening_questions')->insert($data);
        } else {
            db_connect()->table('default_screening_questions')->where('id', $questionId)->update($data);
        }

        return $this->success('Pertanyaan screening default berhasil disimpan.', '#default-questions');
    }

    private function saveVacancyQuestion(int $vacancyId, ?int $questionId): RedirectResponse
    {
        if ($this->findVacancy($vacancyId) === null) {
            return $this->error('Lowongan tidak ditemukan.', '#vacancy-questions');
        }
        $existing = $questionId === null ? null : db_connect()->table('vacancy_screening_questions')->where('id', $questionId)->where('vacancy_id', $vacancyId)->get()->getRowArray();
        if ($questionId !== null && $existing === null) {
            return $this->error('Pertanyaan screening lowongan tidak ditemukan.', $this->vacancyAnchor($vacancyId));
        }
        $data = $this->validatedInput();
        if (is_string($data)) {
            return $this->formError($data, $this->vacancyAnchor($vacancyId));
        }
        if ($questionId === null) {
            $data['vacancy_id'] = $vacancyId;
            $data['source_default_question_id'] = null;
            $data['question_code'] = $this->uniqueVacancyCode($vacancyId, (string) $data['question_text']);
            $data['created_at'] = date('Y-m-d H:i:s');
            db_connect()->table('vacancy_screening_questions')->insert($data);
        } else {
            db_connect()->table('vacancy_screening_questions')->where('id', $questionId)->where('vacancy_id', $vacancyId)->update($data);
        }

        return $this->success('Pertanyaan screening lowongan berhasil disimpan.', $this->vacancyAnchor($vacancyId));
    }

    /** @return array<string, mixed>|string */
    private function validatedInput(): array|string
    {
        $text = trim((string) $this->request->getPost('question_text'));
        $type = trim((string) $this->request->getPost('answer_type'));
        $operator = trim((string) $this->request->getPost('comparison_operator'));
        $expected = trim((string) $this->request->getPost('expected_value'));
        $order = (int) $this->request->getPost('display_order');
        if ($text === '' || mb_strlen($text) > 500) {
            return 'Pertanyaan wajib diisi dan maksimal 500 karakter.';
        }
        if (! in_array($type, self::ANSWER_TYPES, true) || ! in_array($operator, self::OPERATORS, true)) {
            return 'Tipe jawaban atau operator screening tidak valid.';
        }
        if (mb_strlen($expected) > 255 || $order < 0 || $order > 999) {
            return 'Jawaban harapan atau urutan pertanyaan tidak valid.';
        }
        $options = array_values(array_filter(array_map('trim', explode(',', trim((string) $this->request->getPost('answer_options'))))));
        if ($type === 'choice' && count($options) < 2) {
            return 'Tipe pilihan memerlukan minimal dua opsi yang dipisahkan koma.';
        }
        if ($type === 'yes_no') {
            $options = ['YA', 'TIDAK'];
        }

        return [
            'question_text' => $text,
            'answer_type' => $type,
            'answer_options' => $options === [] ? null : json_encode($options, JSON_UNESCAPED_UNICODE),
            'is_required' => $this->request->getPost('is_required') !== null ? 1 : 0,
            'is_knockout' => $this->request->getPost('is_knockout') !== null ? 1 : 0,
            'expected_value' => $expected === '' ? null : $expected,
            'comparison_operator' => $operator === '' ? null : $operator,
            'display_order' => $order,
            'is_active' => $this->request->getPost('is_active') !== null ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function uniqueDefaultCode(string $text): string
    {
        $base = $this->codeBase($text, 65);
        $code = $base;
        while (db_connect()->table('default_screening_questions')->where('question_code', $code)->countAllResults() > 0) {
            $code = mb_substr($base, 0, 58) . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
        }
        return $code;
    }

    private function uniqueVacancyCode(int $vacancyId, string $text): string
    {
        $base = $this->codeBase($text, 42);
        $code = $base;
        while (db_connect()->table('vacancy_screening_questions')->where('vacancy_id', $vacancyId)->where('question_code', $code)->countAllResults() > 0) {
            $code = mb_substr($base, 0, 35) . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
        }
        return $code;
    }

    private function codeBase(string $text, int $limit): string
    {
        $code = trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', mb_strtolower($text)), '_');
        return mb_substr($code !== '' ? $code : 'question', 0, $limit);
    }

    /** @return array<string, mixed>|null */
    private function findVacancy(int $vacancyId): ?array
    {
        return db_connect()->table('vacancies')->where('id', $vacancyId)->where('deleted_at', null)->get()->getRowArray();
    }

    private function vacancyAnchor(int $vacancyId): string
    {
        return '?vacancy_id=' . $vacancyId . '#vacancy-questions';
    }

    private function success(string $message, string $suffix): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/pertanyaan-screening') . $suffix)->with('screening_success', $message);
    }

    private function error(string $message, string $suffix): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/pertanyaan-screening') . $suffix)->with('screening_error', $message);
    }

    private function formError(string $message, string $suffix): RedirectResponse
    {
        $redirect = $this->error($message, $suffix)->withInput();
        $form = trim((string) $this->request->getPost('screening_form'));
        if (preg_match('/^(default|vacancy)-(create|edit-[0-9]+)$/', $form) === 1) {
            $redirect->with('screening_form', $form);
        }
        return $redirect;
    }

    private function copyError(int $vacancyId, string $message): RedirectResponse
    {
        return $this->error($message, $this->vacancyAnchor($vacancyId))
            ->withInput()
            ->with('screening_form', 'vacancy-copy');
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
