<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;
use DateTime;

class VacancyManagementController extends BaseController
{
    private const STATUSES = ['draft', 'open', 'closed', 'archived'];

    public function index(): string
    {
        $this->disableClientCaching();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100);
        $status = trim((string) $this->request->getGet('status'));
        $departmentId = (int) $this->request->getGet('department_id');
        $builder = db_connect()->table('vacancies')
            ->select('vacancies.*, departments.name AS department_name, requirement_groups.name AS requirement_group_name, COUNT(applications.id) AS application_count')
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('requirement_groups', 'requirement_groups.id = vacancies.requirement_group_id')
            ->join('applications', 'applications.vacancy_id = vacancies.id AND applications.deleted_at IS NULL', 'left')
            ->where('vacancies.deleted_at', null)
            ->groupBy('vacancies.id')
            ->orderBy('vacancies.created_at', 'DESC');
        if ($keyword !== '') {
            $builder->groupStart()->like('vacancies.title', $keyword)->orLike('vacancies.code', $keyword)->orLike('vacancies.location', $keyword)->groupEnd();
        }
        if (in_array($status, self::STATUSES, true)) {
            $builder->where('vacancies.status', $status);
        } else {
            $status = '';
        }
        if ($departmentId > 0) {
            $builder->where('vacancies.department_id', $departmentId);
        }

        return view('admin/vacancies', [
            'auth' => $auth,
            'vacancies' => $builder->get()->getResultArray(),
            'departments' => $this->departments(),
            'keyword' => $keyword,
            'selectedStatus' => $status,
            'selectedDepartmentId' => $departmentId,
            'canCreate' => Services::authorization()->can($userId, 'vacancies.create'),
            'canUpdate' => Services::authorization()->can($userId, 'vacancies.update'),
            'canPublish' => Services::authorization()->can($userId, 'vacancies.publish'),
            'canDelete' => Services::authorization()->can($userId, 'vacancies.delete'),
            'canViewDepartments' => Services::authorization()->can($userId, 'departments.view'),
            'canViewRecruitmentSettings' => Services::authorization()->can($userId, 'recruitment.settings.view'),
            'canViewScreeningQuestions' => Services::authorization()->can($userId, 'screening.questions.view'),
            'success' => session()->getFlashdata('vacancy_success'),
            'error' => session()->getFlashdata('vacancy_error'),
        ]);
    }

    public function create(): string
    {
        return $this->formView(null);
    }

    public function store(): RedirectResponse
    {
        $data = $this->validatedVacancyInput(null);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        $userId = $this->currentUserId();
        $now = date('Y-m-d H:i:s');
        $database = db_connect();
        $database->transStart();
        $database->table('vacancies')->insert($data + ['created_by' => $userId, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        $vacancyId = (int) $database->insertID();
        if ($this->request->getPost('use_default_screening') !== null) {
            $this->copyDefaultQuestions($vacancyId);
        }
        $database->transComplete();
        if (! $database->transStatus()) {
            return $this->vacancyError('Lowongan gagal dibuat. Silakan periksa kembali datanya.');
        }

        return redirect()->to(site_url('adminhrdmannakampus/lowongan/' . $vacancyId . '/edit'))->with('vacancy_success', 'Lowongan berhasil dibuat.');
    }

    public function edit(int $vacancyId): string|RedirectResponse
    {
        $vacancy = $this->findVacancy($vacancyId);
        if ($vacancy === null) {
            return $this->vacancyError('Lowongan tidak ditemukan.');
        }

        return $this->formView($vacancy);
    }

    public function update(int $vacancyId): RedirectResponse
    {
        $vacancy = $this->findVacancy($vacancyId);
        if ($vacancy === null) {
            return $this->vacancyError('Lowongan tidak ditemukan.');
        }
        $data = $this->validatedVacancyInput($vacancy);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        try {
            db_connect()->table('vacancies')->where('id', $vacancyId)->update($data + ['updated_by' => $this->currentUserId(), 'updated_at' => date('Y-m-d H:i:s')]);
        } catch (DatabaseException) {
            return $this->vacancyFormError($vacancyId, 'Lowongan gagal diperbarui. Pastikan kode belum digunakan.');
        }

        return redirect()->to(site_url('adminhrdmannakampus/lowongan/' . $vacancyId . '/edit'))->with('vacancy_success', 'Lowongan berhasil diperbarui.');
    }

    public function changeStatus(int $vacancyId): RedirectResponse
    {
        $vacancy = $this->findVacancy($vacancyId);
        $status = trim((string) $this->request->getPost('status'));
        if ($vacancy === null || ! in_array($status, self::STATUSES, true)) {
            return $this->vacancyError('Lowongan atau status tidak valid.');
        }
        $data = ['status' => $status, 'updated_by' => $this->currentUserId(), 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'open' && empty($vacancy['opened_at'])) {
            $data['opened_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'open' && ! empty($vacancy['closed_at']) && strtotime((string) $vacancy['closed_at']) <= time()) {
            $data['closed_at'] = null;
        }
        if ($status === 'closed') {
            $data['closed_at'] = date('Y-m-d H:i:s');
        }
        db_connect()->table('vacancies')->where('id', $vacancyId)->update($data);

        return $this->vacancySuccess('Status lowongan berhasil diubah.');
    }

    public function delete(int $vacancyId): RedirectResponse
    {
        $vacancy = $this->findVacancy($vacancyId);
        if ($vacancy === null) {
            return $this->vacancyError('Lowongan tidak ditemukan.');
        }
        if (db_connect()->table('applications')->where('vacancy_id', $vacancyId)->countAllResults() > 0) {
            return $this->vacancyError('Lowongan sudah memiliki pelamar dan tidak dapat dihapus. Gunakan status Ditutup atau Diarsipkan.');
        }
        db_connect()->table('vacancies')->where('id', $vacancyId)->update(['status' => 'archived', 'deleted_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->currentUserId(), 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->vacancySuccess('Lowongan berhasil dihapus.');
    }

    public function copyScreeningDefaults(int $vacancyId): RedirectResponse
    {
        if ($this->findVacancy($vacancyId) === null) {
            return $this->vacancyError('Lowongan tidak ditemukan.');
        }
        $added = $this->copyDefaultQuestions($vacancyId);

        return $this->vacancyFormSuccess($vacancyId, $added . ' pertanyaan default berhasil ditambahkan.');
    }

    public function createQuestion(int $vacancyId): RedirectResponse
    {
        if ($this->findVacancy($vacancyId) === null) {
            return $this->vacancyError('Lowongan tidak ditemukan.');
        }
        $data = $this->validatedQuestionInput();
        if ($data instanceof RedirectResponse) {
            return $this->vacancyFormError($vacancyId, 'Data pertanyaan screening belum valid.');
        }
        $data['question_code'] = $this->uniqueQuestionCode($vacancyId, (string) $data['question_text']);
        $now = date('Y-m-d H:i:s');
        db_connect()->table('vacancy_screening_questions')->insert($data + ['vacancy_id' => $vacancyId, 'created_at' => $now, 'updated_at' => $now]);

        return $this->vacancyFormSuccess($vacancyId, 'Pertanyaan screening berhasil ditambahkan.');
    }

    public function updateQuestion(int $vacancyId, int $questionId): RedirectResponse
    {
        if (! $this->questionBelongsToVacancy($vacancyId, $questionId)) {
            return $this->vacancyFormError($vacancyId, 'Pertanyaan screening tidak ditemukan.');
        }
        $data = $this->validatedQuestionInput();
        if ($data instanceof RedirectResponse) {
            return $this->vacancyFormError($vacancyId, 'Data pertanyaan screening belum valid.');
        }
        db_connect()->table('vacancy_screening_questions')->where('id', $questionId)->where('vacancy_id', $vacancyId)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);

        return $this->vacancyFormSuccess($vacancyId, 'Pertanyaan screening berhasil diperbarui.');
    }

    public function deleteQuestion(int $vacancyId, int $questionId): RedirectResponse
    {
        if (! $this->questionBelongsToVacancy($vacancyId, $questionId)) {
            return $this->vacancyFormError($vacancyId, 'Pertanyaan screening tidak ditemukan.');
        }
        if (db_connect()->table('application_screening_answers')->where('question_id', $questionId)->countAllResults() > 0) {
            db_connect()->table('vacancy_screening_questions')->where('id', $questionId)->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
            return $this->vacancyFormSuccess($vacancyId, 'Pertanyaan sudah memiliki jawaban kandidat sehingga dinonaktifkan, bukan dihapus.');
        }
        db_connect()->table('vacancy_screening_questions')->where('id', $questionId)->delete();

        return $this->vacancyFormSuccess($vacancyId, 'Pertanyaan screening berhasil dihapus.');
    }

    private function formView(?array $vacancy): string
    {
        $this->disableClientCaching();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $questions = $vacancy === null ? [] : db_connect()->table('vacancy_screening_questions')->where('vacancy_id', $vacancy['id'])->orderBy('display_order', 'ASC')->get()->getResultArray();

        return view('admin/vacancy_form', [
            'auth' => $auth,
            'vacancy' => $vacancy,
            'questions' => $questions,
            'departments' => $this->departments(),
            'requirementGroups' => db_connect()->table('requirement_groups')->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResultArray(),
            'canPublish' => Services::authorization()->can($userId, 'vacancies.publish'),
            'canDelete' => Services::authorization()->can($userId, 'vacancies.delete'),
            'canViewDepartments' => Services::authorization()->can($userId, 'departments.view'),
            'canViewRecruitmentSettings' => Services::authorization()->can($userId, 'recruitment.settings.view'),
            'success' => session()->getFlashdata('vacancy_success'),
            'error' => session()->getFlashdata('vacancy_error'),
        ]);
    }

    /** @return array<string, mixed>|RedirectResponse */
    private function validatedVacancyInput(?array $existing): array|RedirectResponse
    {
        $code = mb_strtolower(trim((string) $this->request->getPost('code')));
        $title = trim((string) $this->request->getPost('title'));
        $departmentId = (int) $this->request->getPost('department_id');
        $groupId = (int) $this->request->getPost('requirement_group_id');
        $minimumAge = trim((string) $this->request->getPost('minimum_age'));
        $maximumAge = trim((string) $this->request->getPost('maximum_age'));
        $headcount = (int) $this->request->getPost('headcount');
        $salaryMin = $this->nullableNumber($this->request->getPost('salary_min'));
        $salaryMax = $this->nullableNumber($this->request->getPost('salary_max'));
        $status = trim((string) $this->request->getPost('status'));

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $code) !== 1 || mb_strlen($code) > 50 || $title === '' || mb_strlen($title) > 150) {
            return $this->formInputError($existing, 'Kode atau judul lowongan tidak valid.');
        }
        $duplicate = db_connect()->table('vacancies')->where('code', $code);
        if ($existing !== null) { $duplicate->where('id !=', $existing['id']); }
        if ($duplicate->countAllResults() > 0) {
            return $this->formInputError($existing, 'Kode lowongan sudah digunakan.');
        }
        if (! $this->referenceExists('departments', $departmentId) || ! $this->referenceExists('requirement_groups', $groupId)) {
            return $this->formInputError($existing, 'Departemen atau kelompok persyaratan tidak valid.');
        }
        $minAge = $minimumAge === '' ? null : (int) $minimumAge;
        $maxAge = $maximumAge === '' ? null : (int) $maximumAge;
        if (($minAge !== null && ($minAge < 15 || $minAge > 80)) || ($maxAge !== null && ($maxAge < 15 || $maxAge > 80)) || ($minAge !== null && $maxAge !== null && $minAge > $maxAge)) {
            return $this->formInputError($existing, 'Rentang usia lowongan tidak valid.');
        }
        if ($headcount < 1 || $headcount > 9999 || $salaryMin === false || $salaryMax === false || (is_float($salaryMin) && is_float($salaryMax) && $salaryMin > $salaryMax)) {
            return $this->formInputError($existing, 'Jumlah kebutuhan atau rentang gaji tidak valid.');
        }
        $openedAt = $this->dateTimeValue($this->request->getPost('opened_at'));
        $closedAt = $this->dateTimeValue($this->request->getPost('closed_at'));
        if ($openedAt === false || $closedAt === false || (is_string($openedAt) && is_string($closedAt) && $openedAt > $closedAt)) {
            return $this->formInputError($existing, 'Jadwal buka dan tutup lowongan tidak valid.');
        }
        if (! Services::authorization()->can($this->currentUserId(), 'vacancies.publish')) {
            $status = $existing['status'] ?? 'draft';
        }
        if (! in_array($status, self::STATUSES, true)) { $status = 'draft'; }

        return [
            'code' => $code, 'title' => $title,
            'summary' => $this->nullableText('summary', 500),
            'job_description' => $this->nullableText('job_description', 10000),
            'responsibilities' => $this->nullableText('responsibilities', 10000),
            'qualifications' => $this->nullableText('qualifications', 10000),
            'department_id' => $departmentId, 'requirement_group_id' => $groupId,
            'location' => $this->nullableText('location', 100), 'employment_type' => $this->nullableText('employment_type', 50),
            'minimum_education' => $this->nullableText('minimum_education', 50), 'minimum_age' => $minAge, 'maximum_age' => $maxAge,
            'headcount' => $headcount, 'salary_min' => $salaryMin === null ? null : $salaryMin, 'salary_max' => $salaryMax === null ? null : $salaryMax,
            'show_salary' => $this->request->getPost('show_salary') !== null ? 1 : 0,
            'status' => $status, 'opened_at' => $openedAt ?: null, 'closed_at' => $closedAt ?: null,
        ];
    }

    /** @return array<string, mixed>|RedirectResponse */
    private function validatedQuestionInput(): array|RedirectResponse
    {
        $text = trim((string) $this->request->getPost('question_text'));
        $type = trim((string) $this->request->getPost('answer_type'));
        $operator = trim((string) $this->request->getPost('comparison_operator'));
        $expected = trim((string) $this->request->getPost('expected_value'));
        $order = (int) $this->request->getPost('display_order');
        if ($text === '' || mb_strlen($text) > 500 || ! in_array($type, ['text', 'number', 'boolean', 'yes_no', 'choice', 'education_level'], true) || ! in_array($operator, ['', 'equals', 'between', 'greater_than_or_equal', 'minimum_education'], true) || $order < 0 || $order > 999) {
            return $this->vacancyError('Pertanyaan screening tidak valid.');
        }
        $options = array_values(array_filter(array_map('trim', explode(',', trim((string) $this->request->getPost('answer_options'))))));
        if ($type === 'choice' && count($options) < 2) {
            return $this->vacancyError('Tipe pilihan memerlukan minimal dua opsi.');
        }
        if ($type === 'yes_no') { $options = ['YA', 'TIDAK']; }

        return ['question_text' => $text, 'answer_type' => $type, 'answer_options' => $options === [] ? null : json_encode($options, JSON_UNESCAPED_UNICODE), 'is_required' => $this->request->getPost('is_required') !== null ? 1 : 0, 'is_knockout' => $this->request->getPost('is_knockout') !== null ? 1 : 0, 'expected_value' => $expected === '' ? null : mb_substr($expected, 0, 255), 'comparison_operator' => $operator === '' ? null : $operator, 'display_order' => $order, 'is_active' => $this->request->getPost('is_active') !== null ? 1 : 0];
    }

    private function copyDefaultQuestions(int $vacancyId): int
    {
        $database = db_connect(); $added = 0;
        $existingCodes = array_column($database->table('vacancy_screening_questions')->select('question_code')->where('vacancy_id', $vacancyId)->get()->getResultArray(), 'question_code');
        foreach ($database->table('default_screening_questions')->where('is_active', 1)->orderBy('display_order', 'ASC')->get()->getResultArray() as $question) {
            if (in_array($question['question_code'], $existingCodes, true)) { continue; }
            $database->table('vacancy_screening_questions')->insert(['vacancy_id' => $vacancyId, 'source_default_question_id' => $question['id'], 'question_code' => $question['question_code'], 'question_text' => $question['question_text'], 'answer_type' => $question['answer_type'], 'answer_options' => $question['answer_options'], 'is_required' => $question['is_required'], 'is_knockout' => $question['is_knockout'], 'expected_value' => $question['expected_value'], 'comparison_operator' => $question['comparison_operator'], 'display_order' => $question['display_order'], 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            $added++;
        }
        return $added;
    }

    private function uniqueQuestionCode(int $vacancyId, string $text): string
    {
        $base = mb_substr(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', mb_strtolower($text)), '_'), 0, 42) ?: 'question';
        $code = $base;
        while (db_connect()->table('vacancy_screening_questions')->where('vacancy_id', $vacancyId)->where('question_code', $code)->countAllResults() > 0) { $code = mb_substr($base, 0, 35) . '_' . substr(bin2hex(random_bytes(3)), 0, 6); }
        return $code;
    }

    private function departments(): array { return db_connect()->table('departments')->where('is_active', 1)->orderBy('display_order', 'ASC')->get()->getResultArray(); }
    private function findVacancy(int $id): ?array { return db_connect()->table('vacancies')->where('id', $id)->where('deleted_at', null)->get()->getRowArray(); }
    private function referenceExists(string $table, int $id): bool { return $id > 0 && db_connect()->table($table)->where('id', $id)->where('is_active', 1)->countAllResults() > 0; }
    private function questionBelongsToVacancy(int $vacancyId, int $questionId): bool { return db_connect()->table('vacancy_screening_questions')->where('id', $questionId)->where('vacancy_id', $vacancyId)->countAllResults() > 0; }
    private function currentUserId(): int { $auth = session()->get('hrd_auth'); return is_array($auth) ? (int) ($auth['user_id'] ?? 0) : 0; }
    private function nullableText(string $field, int $limit): ?string { $value = mb_substr(trim((string) $this->request->getPost($field)), 0, $limit); return $value === '' ? null : $value; }
    private function nullableNumber(mixed $value): float|false|null { $value = trim((string) $value); if ($value === '') { return null; } return is_numeric($value) && (float) $value >= 0 ? (float) $value : false; }
    private function dateTimeValue(mixed $value): string|false|null { $value = trim((string) $value); if ($value === '') { return null; } $date = DateTime::createFromFormat('Y-m-d\TH:i', $value); return $date && $date->format('Y-m-d\TH:i') === $value ? $date->format('Y-m-d H:i:s') : false; }
    private function formInputError(?array $existing, string $message): RedirectResponse { return $existing === null ? redirect()->to(site_url('adminhrdmannakampus/lowongan/baru'))->withInput()->with('vacancy_error', $message) : $this->vacancyFormError((int) $existing['id'], $message); }
    private function vacancySuccess(string $message): RedirectResponse { return redirect()->to(site_url('adminhrdmannakampus/lowongan'))->with('vacancy_success', $message); }
    private function vacancyError(string $message): RedirectResponse { return redirect()->to(site_url('adminhrdmannakampus/lowongan'))->with('vacancy_error', $message); }
    private function vacancyFormSuccess(int $id, string $message): RedirectResponse { return redirect()->to(site_url('adminhrdmannakampus/lowongan/' . $id . '/edit#screening'))->with('vacancy_success', $message); }
    private function vacancyFormError(int $id, string $message): RedirectResponse { return redirect()->to(site_url('adminhrdmannakampus/lowongan/' . $id . '/edit'))->withInput()->with('vacancy_error', $message); }
    private function disableClientCaching(): void { $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache'); }
}
