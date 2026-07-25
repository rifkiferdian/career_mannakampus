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
            'nik'          => trim((string) $this->request->getPost('nik')),
            'batch_number' => mb_strtoupper(trim((string) $this->request->getPost('batch_number'))),
        ];
        $rules = [
            'nik'          => 'required|numeric|exact_length[16]',
            'batch_number' => 'required|max_length[30]|regex_match[/^[A-Z0-9-]+$/]',
        ];
        $messages = [
            'nik' => [
                'required'     => 'NIK wajib diisi.',
                'numeric'      => 'NIK hanya boleh berisi angka.',
                'exact_length' => 'NIK harus terdiri dari 16 digit.',
            ],
            'batch_number' => [
                'required'    => 'Nomor pengajuan wajib diisi.',
                'max_length'  => 'Nomor pengajuan tidak valid.',
                'regex_match' => 'Nomor pengajuan tidak valid.',
            ],
        ];

        if (! $this->validateData($input, $rules, $messages)) {
            return view('application_status', $this->pageData(
                null,
                null,
                $this->validator->getErrors(),
                $input['batch_number'],
            ));
        }

        try {
            $result = Services::applicationStatusLookup()->find($input['nik'], $input['batch_number']);

            if ($result === null) {
                return view('application_status', $this->pageData(
                    'Data lamaran tidak ditemukan. Pastikan NIK dan nomor pengajuan sudah benar.',
                    null,
                    [],
                    $input['batch_number'],
                ));
            }

            return view('application_status', $this->pageData(
                null,
                $result,
                [],
                $input['batch_number'],
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
                $input['batch_number'],
            ));
        }
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
        string $batchNumber = '',
    ): array {
        return [
            'error'        => $error,
            'errors'       => $errors,
            'result'       => $result,
            'batch_number' => $batchNumber,
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
