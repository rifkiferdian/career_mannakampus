<?php

namespace App\Modules\Recruitment\Controllers;

use App\Controllers\BaseController;
use App\Modules\Recruitment\Exceptions\ApplicationRestrictedException;
use App\Modules\Recruitment\Services\ApplicationReceiptPdf;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use DomainException;
use Throwable;

class ApplicationController extends BaseController
{
    public function create(string $vacancyCode): string
    {
        return view('application_form', $this->formData($vacancyCode));
    }

    public function csrf(): ResponseInterface
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON([
                'tokenName' => csrf_token(),
                'tokenHash' => csrf_hash(),
            ]);
    }

    public function store(string $vacancyCode): RedirectResponse
    {
        $data = $this->formData($vacancyCode);

        try {
            $selectedVacancies = $this->selectedVacancies(
                (array) $this->request->getPost('vacancy_ids'),
                (array) $this->request->getPost('position_priorities'),
                $data['vacancy'],
                $data['selectableVacancies'],
            );
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('form_error', $exception->getMessage());
        }

        $questions = array_merge(...array_column($selectedVacancies, 'screening_questions'));
        $rules = $this->validationRules($questions);

        $isValid = $this->validate($rules, $this->validationMessages($questions));
        $errors = array_merge(
            $this->validator->getErrors(),
            $this->workExperienceErrors($this->request->getPost('work_experiences')),
        );

        if (! $isValid || $errors !== []) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $errors);
        }

        try {
            $result = Services::applicationSubmission()->submit(
                $this->request->getPost(),
                $selectedVacancies,
                [
                    'profile_photo'  => $this->request->getFile('profile_photo'),
                    'application_bundle' => $this->request->getFile('application_bundle'),
                ],
                $this->request->getIPAddress(),
                (string) $this->request->getUserAgent(),
            );

            $receiptToken = bin2hex(random_bytes(24));
            $birthDate = trim((string) $this->request->getPost('birth_date'));
            $birthTimestamp = strtotime($birthDate);
            session()->setTempdata('application_receipt_' . $receiptToken, [
                'batch_number' => $result['batch_number'],
                'submitted_at' => date('d/m/Y H:i'),
                'profile' => [
                    'full_name'      => trim((string) $this->request->getPost('full_name')),
                    'email'          => trim((string) $this->request->getPost('email')),
                    'phone'          => trim((string) $this->request->getPost('phone')),
                    'birth_place'    => trim((string) $this->request->getPost('birth_place')),
                    'birth_date'     => $birthTimestamp === false ? $birthDate : date('d/m/Y', $birthTimestamp),
                    'last_education' => trim((string) $this->request->getPost('last_education')),
                    'institution'    => trim((string) $this->request->getPost('institution')),
                    'major'          => trim((string) $this->request->getPost('major')),
                ],
                'applications' => array_map(
                    static fn (array $application): array => [
                        'title'              => $application['title'],
                        'application_number' => $application['application_number'],
                        'preference_order'   => $application['preference_order'],
                    ],
                    $result['applications'],
                ),
            ], 1800);
            $result['receipt_token'] = $receiptToken;
            session()->setFlashdata('submission_result', $result);

            return redirect()->to(site_url('lamaran/berhasil'));
        } catch (ApplicationRestrictedException $exception) {
            $token = bin2hex(random_bytes(24));
            session()->setTempdata(
                'application_restriction_' . $token,
                $exception->restriction(),
                900,
            );

            return redirect()->to(site_url('lamaran/tidak-dapat-diproses?token=' . $token));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('form_error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', '[Recruitment] Gagal menyimpan lamaran: {message}', [
                'message'   => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('form_error', 'Lamaran belum berhasil disimpan. Silakan periksa data dan coba kembali.');
        }
    }

    public function success(): RedirectResponse|string
    {
        $result = session()->getFlashdata('submission_result');
        if (!is_array($result)) {
            return redirect()->to(site_url('lowongan'));
        }

        return view('application_success', ['result' => $result]);
    }

    public function restricted(): RedirectResponse|string
    {
        $token = trim((string) $this->request->getGet('token'));
        if (preg_match('/\A[a-f0-9]{48}\z/D', $token) !== 1) {
            return redirect()->to(site_url('lowongan'));
        }

        $restriction = session()->getTempdata('application_restriction_' . $token);
        if (! is_array($restriction) || ! in_array($restriction['type'] ?? null, ['blacklist', 'cooldown'], true)) {
            return redirect()->to(site_url('lowongan'));
        }

        if (($restriction['type'] ?? null) === 'cooldown') {
            $restriction['rejected_date_label'] = $this->indonesianDate((string) ($restriction['rejected_at'] ?? ''));
            $restriction['available_date_label'] = $this->indonesianDate((string) ($restriction['available_at'] ?? ''));
        } else {
            $restriction['expiry_label'] = ! empty($restriction['is_permanent'])
                ? 'Tidak terbatas (permanen)'
                : $this->indonesianDate((string) ($restriction['ends_at'] ?? ''));
        }

        return view('application_restricted', ['restriction' => $restriction]);
    }

    public function receipt(string $token): ResponseInterface
    {
        if (preg_match('/\A[a-f0-9]{48}\z/D', $token) !== 1) {
            throw PageNotFoundException::forPageNotFound('Bukti lamaran tidak ditemukan.');
        }

        $receipt = session()->getTempdata('application_receipt_' . $token);
        if (! is_array($receipt)) {
            throw PageNotFoundException::forPageNotFound('Bukti lamaran telah kedaluwarsa atau tidak ditemukan.');
        }

        $pdf = (new ApplicationReceiptPdf())->generate($receipt);
        $filenameNumber = preg_replace('/[^A-Za-z0-9-]+/', '-', (string) ($receipt['batch_number'] ?? 'lamaran'));

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="bukti-lamaran-' . $filenameNumber . '.pdf"')
            ->setHeader('Content-Length', (string) strlen($pdf))
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setBody($pdf);
    }

    private function indonesianDate(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '-';
        }

        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp)
            . ' pukul ' . date('H.i', $timestamp) . ' WIB';
    }

    /**
     * @return array{
     *     vacancy: array<string, mixed>,
     *     selectableVacancies: list<array<string, mixed>>
     * }
     */
    private function formData(string $vacancyCode): array
    {
        $vacancy = Services::vacancyCatalog()->openVacancyByCode($vacancyCode);
        if ($vacancy === null) {
            throw PageNotFoundException::forPageNotFound('Lowongan tidak ditemukan atau sudah ditutup.');
        }

        return [
            'vacancy'              => $vacancy,
            'selectableVacancies' => Services::vacancyCatalog()->selectableVacancies($vacancyCode),
        ];
    }

    /**
     * @param list<mixed> $submittedIds
     * @param array<string, mixed> $submittedPriorities
     * @param array<string, mixed> $primaryVacancy
     * @param list<array<string, mixed>> $selectableVacancies
     *
     * @return list<array<string, mixed>>
     */
    private function selectedVacancies(
        array $submittedIds,
        array $submittedPriorities,
        array $primaryVacancy,
        array $selectableVacancies,
    ): array {
        $selectedIds = array_values(array_unique(array_map('intval', $submittedIds)));

        if (!in_array((int) $primaryVacancy['id'], $selectedIds, true)) {
            $selectedIds[] = (int) $primaryVacancy['id'];
        }

        $maximum = 3;
        if ($selectedIds === [] || count($selectedIds) > $maximum) {
            throw new DomainException("Pilih minimal satu dan maksimal {$maximum} posisi.");
        }

        $selectableById = [];
        foreach ($selectableVacancies as $vacancy) {
            $selectableById[(int) $vacancy['id']] = $vacancy;
        }

        $selectedVacancies = [];
        foreach ($selectedIds as $selectedId) {
            if (!isset($selectableById[$selectedId])) {
                throw new DomainException('Terdapat posisi yang tidak tersedia atau sudah tidak aktif.');
            }

            $priority = (int) ($submittedPriorities[(string) $selectedId] ?? 0);
            $selectableById[$selectedId]['preference_order'] = $priority;
            $selectedVacancies[] = $selectableById[$selectedId];
        }

        $priorities = array_column($selectedVacancies, 'preference_order');
        $expectedPriorities = range(1, count($selectedVacancies));
        sort($priorities);

        if ($priorities !== $expectedPriorities) {
            throw new DomainException('Urutan prioritas posisi tidak valid. Silakan pilih kembali posisi yang diinginkan.');
        }

        usort(
            $selectedVacancies,
            static fn (array $first, array $second): int =>
                $first['preference_order'] <=> $second['preference_order'],
        );

        return $selectedVacancies;
    }

    /**
     * @param list<array<string, mixed>> $questions
     *
     * @return array<string, string>
     */
    private function validationRules(array $questions): array
    {
        $rules = [
            'nik'                 => 'required|numeric|exact_length[16]',
            'full_name'           => 'required|max_length[150]',
            'email'               => 'required|valid_email|max_length[150]',
            'phone'               => 'required|regex_match[/^(?:\\+?62|0)[0-9]{8,13}$/]',
            'birth_place'         => 'required|max_length[100]',
            'birth_date'          => 'required|valid_date[Y-m-d]',
            'height_cm'           => 'permit_empty|integer|greater_than_equal_to[100]|less_than_equal_to[250]',
            'gender'              => 'required|in_list[PRIA,WANITA]',
            'marital_status'      => 'required|in_list[BELUM MENIKAH,MENIKAH,CERAI]',
            'religion'            => 'required|max_length[30]',
            'address'             => 'required|min_length[10]|max_length[1000]',
            'last_education'      => 'required|in_list[SMP,SMA/SMK,D1,D3,S1,S2]',
            'institution'         => 'required|max_length[150]',
            'major'               => 'required|max_length[150]',
            'gpa'                 => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[4]',
            'training_experience' => 'permit_empty|max_length[3000]',
            'work_motivation'     => 'required|min_length[20]|max_length[5000]',
            'career_goal'         => 'required|min_length[20]|max_length[5000]',
            'privacy_consent'     => 'required|in_list[1]',
            'profile_photo'       => 'permit_empty|max_size[profile_photo,2048]|ext_in[profile_photo,jpg,jpeg,png]|is_image[profile_photo]',
            'application_bundle'  => 'uploaded[application_bundle]|max_size[application_bundle,2048]|ext_in[application_bundle,pdf]',
        ];

        foreach ($questions as $question) {
            if ((int) $question['is_required'] === 1) {
                $rules['screening.' . $question['id']] = 'required|max_length[255]';
            }
        }

        return $rules;
    }

    /**
     * @param list<array<string, mixed>> $questions
     * @return array<string, array<string, string>>
     */
    private function validationMessages(array $questions): array
    {
        $messages = [
            'nik' => [
                'required' => 'NIK wajib diisi.',
                'numeric' => 'NIK hanya boleh berisi angka.',
                'exact_length' => 'NIK harus terdiri dari 16 angka.',
            ],
            'full_name' => ['required' => 'Nama lengkap wajib diisi.'],
            'email' => ['required' => 'Email wajib diisi.', 'valid_email' => 'Format email belum valid.'],
            'phone' => ['required' => 'Nomor WhatsApp wajib diisi.', 'regex_match' => 'Format nomor WhatsApp belum valid. Gunakan contoh 081234567890.'],
            'birth_place' => ['required' => 'Tempat lahir wajib diisi.'],
            'birth_date' => ['required' => 'Tanggal lahir wajib diisi.', 'valid_date' => 'Tanggal lahir belum valid.'],
            'gender' => ['required' => 'Jenis kelamin wajib dipilih.', 'in_list' => 'Pilihan jenis kelamin belum valid.'],
            'marital_status' => ['required' => 'Status pernikahan wajib dipilih.', 'in_list' => 'Pilihan status pernikahan belum valid.'],
            'religion' => ['required' => 'Agama wajib dipilih.'],
            'address' => [
                'required' => 'Alamat lengkap wajib diisi.',
                'min_length' => 'Alamat lengkap minimal 10 karakter.',
                'max_length' => 'Alamat lengkap maksimal 1.000 karakter.',
            ],
            'last_education' => ['required' => 'Jenjang pendidikan wajib dipilih.', 'in_list' => 'Jenjang pendidikan belum valid.'],
            'institution' => ['required' => 'Nama sekolah atau perguruan tinggi wajib diisi.'],
            'major' => ['required' => 'Jurusan wajib diisi.'],
            'work_motivation' => ['required' => 'Motivasi bekerja wajib diisi.', 'min_length' => 'Motivasi bekerja minimal 20 karakter.'],
            'career_goal' => ['required' => 'Target atau impian wajib diisi.', 'min_length' => 'Target atau impian minimal 20 karakter.'],
            'privacy_consent' => ['required' => 'Persetujuan pemrosesan data wajib dicentang.', 'in_list' => 'Persetujuan pemrosesan data wajib dicentang.'],
            'profile_photo' => [
                'max_size' => 'Ukuran foto profil maksimal 2 MB.',
                'ext_in' => 'Foto profil harus berformat JPG atau PNG.',
                'is_image' => 'File foto profil belum valid.',
            ],
            'application_bundle' => [
                'uploaded' => 'Berkas lamaran PDF wajib diunggah.',
                'max_size' => 'Ukuran berkas lamaran maksimal 2 MB. Silakan kompres atau pilih berkas lain.',
                'ext_in' => 'Berkas lamaran harus berformat PDF.',
            ],
        ];

        foreach ($questions as $question) {
            $messages['screening.' . $question['id']] = [
                'required' => 'Pertanyaan screening “' . $question['question_text'] . '” wajib dijawab.',
                'max_length' => 'Jawaban screening maksimal 255 karakter.',
            ];
        }

        return $messages;
    }

    /** @return array<string, string> */
    private function workExperienceErrors(mixed $submittedExperiences): array
    {
        if ($submittedExperiences === null || $submittedExperiences === '') {
            return [];
        }
        if (! is_array($submittedExperiences)) {
            return ['work_experiences' => 'Data pengalaman kerja tidak valid.'];
        }
        if (count($submittedExperiences) > 10) {
            return ['work_experiences' => 'Maksimal 10 riwayat perusahaan dapat ditambahkan.'];
        }

        $errors = [];
        $currentYear = (int) date('Y');
        foreach (array_values($submittedExperiences) as $index => $experience) {
            if (! is_array($experience)) {
                $errors['work_experiences.' . $index] = 'Data perusahaan ke-' . ($index + 1) . ' tidak valid.';
                continue;
            }

            $company = trim((string) ($experience['company_name'] ?? ''));
            $positionTitle = trim((string) ($experience['position_title'] ?? ''));
            $startYear = trim((string) ($experience['start_year'] ?? ''));
            $endYear = trim((string) ($experience['end_year'] ?? ''));
            $responsibilities = trim((string) ($experience['responsibilities'] ?? ''));
            if ($company === '' && $positionTitle === '' && $startYear === '' && $endYear === '' && $responsibilities === '') {
                continue;
            }

            $prefix = 'Perusahaan ke-' . ($index + 1) . ': ';
            if ($company === '' || mb_strlen($company) > 150) {
                $errors['work_experiences.' . $index . '.company_name'] = $prefix . 'nama PT/perusahaan wajib diisi dan maksimal 150 karakter.';
            }
            if ($positionTitle === '' || mb_strlen($positionTitle) > 150) {
                $errors['work_experiences.' . $index . '.position_title'] = $prefix . 'jabatan/posisi wajib diisi dan maksimal 150 karakter.';
            }
            if (preg_match('/^\d{4}$/', $startYear) !== 1 || (int) $startYear < 1950 || (int) $startYear > $currentYear) {
                $errors['work_experiences.' . $index . '.start_year'] = $prefix . 'tahun masuk tidak valid.';
            }
            if ($endYear !== '' && (preg_match('/^\d{4}$/', $endYear) !== 1 || (int) $endYear < (int) $startYear || (int) $endYear > $currentYear + 1)) {
                $errors['work_experiences.' . $index . '.end_year'] = $prefix . 'tahun akhir harus sama atau setelah tahun masuk.';
            }
            if ($responsibilities === '' || mb_strlen($responsibilities) > 5000) {
                $errors['work_experiences.' . $index . '.responsibilities'] = $prefix . 'deskripsi tugas dan tanggung jawab wajib diisi dan maksimal 5.000 karakter.';
            }
        }

        return $errors;
    }
}
