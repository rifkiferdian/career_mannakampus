<?php

namespace App\Modules\Recruitment\Controllers;

use App\Controllers\BaseController;
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

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
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
            'work_experience'     => 'permit_empty|max_length[5000]',
            'work_motivation'     => 'required|min_length[20]|max_length[5000]',
            'career_goal'         => 'required|min_length[20]|max_length[5000]',
            'privacy_consent'     => 'required|in_list[1]',
            'profile_photo'       => 'permit_empty|max_size[profile_photo,2048]|ext_in[profile_photo,jpg,jpeg,png]|is_image[profile_photo]',
            'application_bundle'  => 'uploaded[application_bundle]|max_size[application_bundle,10240]|ext_in[application_bundle,pdf]',
        ];

        foreach ($questions as $question) {
            if ((int) $question['is_required'] === 1) {
                $rules['screening.' . $question['id']] = 'required|max_length[255]';
            }
        }

        return $rules;
    }
}
