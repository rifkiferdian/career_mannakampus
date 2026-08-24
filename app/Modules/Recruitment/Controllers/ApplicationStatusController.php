<?php

namespace App\Modules\Recruitment\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

class ApplicationStatusController extends BaseController
{
    public function index(): string
    {
        $this->disableClientCaching();

        return view('application_status', $this->pageData());
    }

    public function lookup(): string
    {
        $this->disableClientCaching();

        $throttleKey = 'application_status_' . hash('sha256', $this->request->getIPAddress());
        if (! Services::throttler()->check($throttleKey, 8, 60)) {
            $this->response->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS);

            return view('application_status', $this->pageData(
                'Terlalu banyak percobaan. Tunggu satu menit sebelum mencoba kembali.',
            ));
        }

        $input = [
            'nik' => trim((string) $this->request->getPost('nik')),
        ];
        $rules = [
            'nik' => 'required|numeric|exact_length[16]',
        ];
        $messages = [
            'nik' => [
                'required'     => 'NIK wajib diisi.',
                'numeric'      => 'NIK hanya boleh berisi angka.',
                'exact_length' => 'NIK harus terdiri dari 16 digit.',
            ],
        ];

        if (! $this->validateData($input, $rules, $messages)) {
            return view('application_status', $this->pageData(
                null,
                null,
                $this->validator->getErrors(),
            ));
        }

        try {
            $result = Services::applicationStatusLookup()->find($input['nik']);

            if ($result === null) {
                return view('application_status', $this->pageData(
                    'Data lamaran tidak ditemukan. Pastikan 16 digit NIK sudah benar.',
                    null,
                    [],
                ));
            }

            $scheduleIds = [];
            foreach ($result['applications'] as $application) {
                if (isset($application['schedule']['id'])) {
                    $scheduleIds[] = (int) $application['schedule']['id'];
                }
            }
            session()->set('application_status_schedule_access', [
                'ids' => $scheduleIds,
                'expires_at' => time() + 900,
            ]);

            return view('application_status', $this->pageData(
                null,
                $result,
                [],
            ));
        } catch (Throwable $exception) {
            log_message('error', '[Recruitment] Pengecekan status lamaran gagal: {message}', [
                'message'   => $exception->getMessage(),
                'exception' => $exception,
            ]);

            $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);

            return view('application_status', $this->pageData(
                'Status lamaran sedang tidak dapat diperiksa. Silakan coba kembali nanti.',
                null,
                [],
            ));
        }
    }

    public function respond(int $scheduleId)
    {
        $this->disableClientCaching();
        $access = session()->get('application_status_schedule_access');
        $allowedIds = is_array($access) ? array_map('intval', (array) ($access['ids'] ?? [])) : [];
        if (! is_array($access) || (int) ($access['expires_at'] ?? 0) < time() || ! in_array($scheduleId, $allowedIds, true)) {
            return redirect()->to(site_url('lamaran/status'))->with('status_message', 'Sesi konfirmasi telah berakhir. Silakan cek kembali status lamaran Anda.');
        }
        $schedule = Services::recruitmentSchedule()->find($scheduleId);
        $response = trim((string) $this->request->getPost('response'));
        $note = mb_substr(trim((string) $this->request->getPost('candidate_note')), 0, 2000);
        if ($schedule === null || (string) $schedule['status'] !== 'scheduled' || strtotime((string) $schedule['confirmation_deadline_at']) < time()) {
            return redirect()->to(site_url('lamaran/status'))->with('status_message', 'Jadwal tidak tersedia atau batas konfirmasinya sudah berakhir.');
        }
        if ($response === 'reschedule_requested' && mb_strlen($note) < 5) {
            return redirect()->to(site_url('lamaran/status'))->with('status_message', 'Tuliskan alasan permintaan jadwal ulang minimal 5 karakter.');
        }
        if (! in_array($response, ['confirmed', 'reschedule_requested'], true)) {
            return redirect()->to(site_url('lamaran/status'))->with('status_message', 'Pilihan konfirmasi tidak valid.');
        }
        Services::recruitmentSchedule()->setStatus($scheduleId, $response, null, $note);

        return redirect()->to(site_url('lamaran/status'))->with('status_success', $response === 'confirmed' ? 'Konfirmasi kehadiran berhasil disimpan.' : 'Permintaan jadwal ulang berhasil dikirim kepada recruiter.');
    }

    /**
     * @param array<string, mixed>|null $result
     * @param array<string, string> $errors
     *
     * @return array<string, mixed>
     */
    private function pageData(
        ?string $error = null,
        ?array $result = null,
        array $errors = [],
    ): array {
        return [
            'error'        => $error,
            'errors'       => $errors,
            'result'       => $result,
            'statusMessage' => session()->getFlashdata('status_message'),
            'statusSuccess' => session()->getFlashdata('status_success'),
        ];
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
