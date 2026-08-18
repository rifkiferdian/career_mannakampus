<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class RecruitmentSettingsController extends BaseController
{
    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);

        return view('admin/recruitment_settings', [
            'auth'               => $auth,
            'canManage'          => Services::authorization()->can($userId, 'recruitment.settings.manage'),
            'canViewDepartments' => Services::authorization()->can($userId, 'departments.view'),
            'canViewVacancies' => Services::authorization()->can($userId, 'vacancies.view'),
            'rejectionTemplates' => $database->table('rejection_reason_templates')->orderBy('display_order', 'ASC')->get()->getResultArray(),
            'success'            => session()->getFlashdata('settings_success'),
            'error'              => session()->getFlashdata('settings_error'),
            'openSettingsModal'  => (string) (session()->getFlashdata('settings_form') ?? ''),
        ]);
    }

    public function createRejectionTemplate(): RedirectResponse
    {
        return $this->saveRejectionTemplate(null);
    }

    public function updateRejectionTemplate(int $templateId): RedirectResponse
    {
        return $this->saveRejectionTemplate($templateId);
    }

    public function toggleRejectionTemplate(int $templateId): RedirectResponse
    {
        $template = db_connect()->table('rejection_reason_templates')->where('id', $templateId)->get()->getRowArray();
        if ($template === null) {
            return $this->settingsError('Template penolakan tidak ditemukan.', '#rejections');
        }
        db_connect()->table('rejection_reason_templates')->where('id', $templateId)->update([
            'is_active'  => (int) $template['is_active'] === 1 ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->settingsSuccess('Status template penolakan berhasil diubah.', '#rejections');
    }

    public function deleteRejectionTemplate(int $templateId): RedirectResponse
    {
        if (db_connect()->table('rejection_reason_templates')->where('id', $templateId)->countAllResults() === 0) {
            return $this->settingsError('Template penolakan tidak ditemukan.', '#rejections');
        }
        db_connect()->table('rejection_reason_templates')->where('id', $templateId)->delete();

        return $this->settingsSuccess('Template alasan penolakan berhasil dihapus.', '#rejections');
    }

    public function createScreeningQuestion(): RedirectResponse
    {
        return $this->saveScreeningQuestion(null);
    }

    public function updateScreeningQuestion(int $questionId): RedirectResponse
    {
        return $this->saveScreeningQuestion($questionId);
    }

    public function toggleScreeningQuestion(int $questionId): RedirectResponse
    {
        $question = db_connect()->table('default_screening_questions')->where('id', $questionId)->get()->getRowArray();
        if ($question === null) {
            return $this->settingsError('Pertanyaan screening tidak ditemukan.', '#screening');
        }
        db_connect()->table('default_screening_questions')->where('id', $questionId)->update([
            'is_active'  => (int) $question['is_active'] === 1 ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->settingsSuccess('Status pertanyaan screening berhasil diubah.', '#screening');
    }

    public function deleteScreeningQuestion(int $questionId): RedirectResponse
    {
        if (db_connect()->table('default_screening_questions')->where('id', $questionId)->countAllResults() === 0) {
            return $this->settingsError('Pertanyaan screening tidak ditemukan.', '#screening');
        }
        db_connect()->table('default_screening_questions')->where('id', $questionId)->delete();

        return $this->settingsSuccess('Pertanyaan screening default berhasil dihapus.', '#screening');
    }

    private function saveRejectionTemplate(?int $templateId): RedirectResponse
    {
        $title = trim((string) $this->request->getPost('title'));
        $reasonText = trim((string) $this->request->getPost('reason_text'));
        $order = (int) $this->request->getPost('display_order');
        if ($title === '' || mb_strlen($title) > 150) {
            return $this->settingsError('Judul template wajib diisi dan maksimal 150 karakter.', '#rejections');
        }
        if ($reasonText === '' || mb_strlen($reasonText) > 1000) {
            return $this->settingsError('Isi alasan wajib diisi dan maksimal 1.000 karakter.', '#rejections');
        }
        if ($order < 1 || $order > 999) {
            return $this->settingsError('Urutan template harus antara 1–999.', '#rejections');
        }

        $data = [
            'title'         => $title,
            'reason_text'   => $reasonText,
            'display_order' => $order,
            'is_active'     => $this->request->getPost('is_active') !== null ? 1 : 0,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if ($templateId === null) {
            $data['created_at'] = date('Y-m-d H:i:s');
            db_connect()->table('rejection_reason_templates')->insert($data);
        } else {
            $exists = db_connect()->table('rejection_reason_templates')->where('id', $templateId)->countAllResults() > 0;
            if (! $exists) {
                return $this->settingsError('Template penolakan tidak ditemukan.', '#rejections');
            }
            db_connect()->table('rejection_reason_templates')->where('id', $templateId)->update($data);
        }

        return $this->settingsSuccess('Template alasan penolakan berhasil disimpan.', '#rejections');
    }

    private function saveScreeningQuestion(?int $questionId): RedirectResponse
    {
        $text = trim((string) $this->request->getPost('question_text'));
        $answerType = (string) $this->request->getPost('answer_type');
        $operator = trim((string) $this->request->getPost('comparison_operator'));
        $expected = trim((string) $this->request->getPost('expected_value'));
        $optionsText = trim((string) $this->request->getPost('answer_options'));
        $order = (int) $this->request->getPost('display_order');
        $allowedTypes = ['text', 'number', 'yes_no', 'choice'];
        $allowedOperators = ['', 'equals', 'between', 'greater_than_or_equal', 'minimum_education'];

        if ($text === '' || mb_strlen($text) > 500) {
            return $this->settingsError('Pertanyaan wajib diisi dan maksimal 500 karakter.', '#screening');
        }
        if (! in_array($answerType, $allowedTypes, true) || ! in_array($operator, $allowedOperators, true)) {
            return $this->settingsError('Tipe jawaban atau operator tidak valid.', '#screening');
        }
        if (mb_strlen($expected) > 255) {
            return $this->settingsError('Jawaban harapan maksimal 255 karakter.', '#screening');
        }
        if ($order < 1 || $order > 999) {
            return $this->settingsError('Urutan pertanyaan harus antara 1–999.', '#screening');
        }

        $options = array_values(array_filter(array_map('trim', explode(',', $optionsText)), static fn (string $option): bool => $option !== ''));
        if ($answerType === 'choice' && count($options) < 2) {
            return $this->settingsError('Pertanyaan pilihan memerlukan minimal dua opsi.', '#screening');
        }
        if ($answerType === 'yes_no') {
            $options = ['YA', 'TIDAK'];
        }

        $data = [
            'question_text'       => $text,
            'answer_type'         => $answerType,
            'answer_options'      => $options !== [] ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'is_required'         => $this->request->getPost('is_required') !== null ? 1 : 0,
            'is_knockout'         => $this->request->getPost('is_knockout') !== null ? 1 : 0,
            'expected_value'      => $expected !== '' ? $expected : null,
            'comparison_operator' => $operator !== '' ? $operator : null,
            'display_order'       => $order,
            'is_active'           => $this->request->getPost('is_active') !== null ? 1 : 0,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];
        if ($questionId === null) {
            $data['question_code'] = $this->uniqueQuestionCode($text);
            $data['created_at'] = date('Y-m-d H:i:s');
            db_connect()->table('default_screening_questions')->insert($data);
        } else {
            $exists = db_connect()->table('default_screening_questions')->where('id', $questionId)->countAllResults() > 0;
            if (! $exists) {
                return $this->settingsError('Pertanyaan screening tidak ditemukan.', '#screening');
            }
            db_connect()->table('default_screening_questions')->where('id', $questionId)->update($data);
        }

        return $this->settingsSuccess('Pertanyaan screening default berhasil disimpan.', '#screening');
    }

    private function uniqueQuestionCode(string $question): string
    {
        $code = mb_strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $question), '_'));
        $code = mb_substr($code !== '' ? $code : 'question', 0, 65);
        $candidate = $code;
        while (db_connect()->table('default_screening_questions')->where('question_code', $candidate)->countAllResults() > 0) {
            $candidate = $code . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
        }

        return $candidate;
    }

    private function settingsSuccess(string $message, string $anchor): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/pengaturan-rekrutmen') . $anchor)->with('settings_success', $message);
    }

    private function settingsError(string $message, string $anchor): RedirectResponse
    {
        $redirect = redirect()->to(site_url('adminhrdmannakampus/pengaturan-rekrutmen') . $anchor)
            ->with('settings_error', $message);
        $form = trim((string) $this->request->getPost('settings_form'));

        if (preg_match('/^(rejection|screening)-(create|edit-[0-9]+)$/', $form) === 1) {
            $redirect->with('settings_form', $form)->withInput();
        }

        return $redirect;
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
