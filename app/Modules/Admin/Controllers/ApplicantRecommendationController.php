<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class ApplicantRecommendationController extends BaseController
{
    private const RECOMMENDATIONS = ['continue', 'hold', 'reject'];

    public function save(int $applicantId): RedirectResponse
    {
        $database = db_connect();
        $userId = (int) (session()->get('hrd_auth')['user_id'] ?? 0);
        $applicant = $database->table('applicants')->select('id, full_name, assigned_hrd_team_id')->where('id', $applicantId)->where('deleted_at', null)->get()->getRowArray();
        if ($applicant === null || ! $this->canManage($userId, (int) ($applicant['assigned_hrd_team_id'] ?? 0))) {
            return $this->failure($applicantId, 'Pelamar tidak ditemukan atau tidak dapat Anda nilai.');
        }

        $recommendation = trim((string) $this->request->getPost('recommendation'));
        $notes = trim((string) $this->request->getPost('notes'));
        if (! in_array($recommendation, self::RECOMMENDATIONS, true)) {
            return $this->failure($applicantId, 'Pilih rekomendasi Lanjut, Tahan, atau Tolak.');
        }
        if (mb_strlen($notes) > 5000) {
            return $this->failure($applicantId, 'Catatan rekomendasi maksimal 5.000 karakter.');
        }

        $aspects = $database->table('recommendation_aspects')->where('deleted_at', null)->where('is_active', 1)->orderBy('display_order')->get()->getResultArray();
        $answers = is_array($this->request->getPost('answers')) ? $this->request->getPost('answers') : [];
        $validatedAnswers = [];
        foreach ($aspects as $aspect) {
            $aspectId = (int) $aspect['id'];
            $answer = trim((string) ($answers[$aspectId] ?? ''));
            if ((int) $aspect['is_required'] === 1 && $answer === '') {
                return $this->failure($applicantId, 'Aspek “' . $aspect['name'] . '” wajib diisi.');
            }
            if ($answer === '') {
                $validatedAnswers[$aspectId] = null;
                continue;
            }
            $error = $this->answerError($aspect, $answer);
            if ($error !== null) {
                return $this->failure($applicantId, $error);
            }
            $validatedAnswers[$aspectId] = $answer;
        }

        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $existing = $database->table('applicant_recommendations')->select('id')->where('applicant_id', $applicantId)->get()->getRowArray();
        $recommendationData = ['recommendation' => $recommendation, 'notes' => $notes !== '' ? $notes : null, 'updated_by' => $userId, 'updated_at' => $now];
        if ($existing === null) {
            $database->table('applicant_recommendations')->insert($recommendationData + ['applicant_id' => $applicantId, 'created_at' => $now]);
            $recommendationId = (int) $database->insertID();
        } else {
            $recommendationId = (int) $existing['id'];
            $database->table('applicant_recommendations')->where('id', $recommendationId)->update($recommendationData);
        }
        foreach ($validatedAnswers as $aspectId => $answer) {
            $answerBuilder = $database->table('applicant_recommendation_answers')->where('recommendation_id', $recommendationId)->where('aspect_id', $aspectId);
            $existingAnswer = $answerBuilder->get()->getRowArray();
            if ($answer === null) {
                if ($existingAnswer !== null) {
                    $database->table('applicant_recommendation_answers')->where('id', $existingAnswer['id'])->delete();
                }
            } elseif ($existingAnswer === null) {
                $database->table('applicant_recommendation_answers')->insert(['recommendation_id' => $recommendationId, 'aspect_id' => $aspectId, 'answer_value' => $answer, 'created_at' => $now, 'updated_at' => $now]);
            } else {
                $database->table('applicant_recommendation_answers')->where('id', $existingAnswer['id'])->update(['answer_value' => $answer, 'updated_at' => $now]);
            }
        }
        $database->transComplete();
        if (! $database->transStatus()) {
            return $this->failure($applicantId, 'Penilaian pelamar gagal disimpan.');
        }

        return redirect()->to($this->detailUrl($applicantId))->with('candidate_success', 'Penilaian dan rekomendasi pelamar berhasil disimpan.');
    }

    /** @param array<string, mixed> $aspect */
    private function answerError(array $aspect, string $answer): ?string
    {
        $name = (string) $aspect['name'];
        if ($aspect['input_type'] === 'scale_1_5' && (! ctype_digit($answer) || (int) $answer < 1 || (int) $answer > 5)) {
            return 'Nilai untuk aspek “' . $name . '” harus antara 1–5.';
        }
        if ($aspect['input_type'] === 'yes_no' && ! in_array($answer, ['Ya', 'Tidak'], true)) {
            return 'Jawaban untuk aspek “' . $name . '” tidak valid.';
        }
        if ($aspect['input_type'] === 'choice') {
            $options = json_decode((string) ($aspect['options_json'] ?? ''), true);
            if (! is_array($options) || ! in_array($answer, $options, true)) {
                return 'Pilihan untuk aspek “' . $name . '” tidak valid.';
            }
        }
        if ($aspect['input_type'] === 'text' && mb_strlen($answer) > 1000) {
            return 'Jawaban untuk aspek “' . $name . '” maksimal 1.000 karakter.';
        }

        return null;
    }

    private function canManage(int $userId, int $teamId): bool
    {
        if (! Services::authorization()->can($userId, 'recommendations.manage') || $teamId <= 0) {
            return false;
        }
        if (Services::authorization()->can($userId, 'hrd.teams.manage')) {
            return true;
        }

        return db_connect()->table('hrd_team_users')->where('user_id', $userId)->where('hrd_team_id', $teamId)->countAllResults() > 0;
    }

    private function failure(int $applicantId, string $message): RedirectResponse
    {
        return redirect()->to($this->detailUrl($applicantId))->with('candidate_error', $message)->with('recommendation_form', 'open')->withInput();
    }

    private function detailUrl(int $applicantId): string
    {
        $url = site_url('adminhrdmannakampus/pelamar/' . $applicantId);
        if ($this->request->getGet('source') === 'division') {
            $url .= '?source=division&team_id=' . max(0, (int) $this->request->getGet('team_id'));
        }

        return $url;
    }
}
