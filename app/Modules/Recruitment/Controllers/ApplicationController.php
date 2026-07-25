<?php

namespace App\Modules\Recruitment\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
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
                $data['compatibleVacancies'],
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
                    'cv'             => $this->request->getFile('cv'),
                    'document_bundle'=> $this->request->getFile('document_bundle'),
                ],
                $this->request->getIPAddress(),
                (string) $this->request->getUserAgent(),
            );

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

    /**
     * @return array{
     *     vacancy: array<string, mixed>,
     *     compatibleVacancies: list<array<string, mixed>>
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
            'compatibleVacancies' => Services::vacancyCatalog()->compatibleVacancies($vacancyCode),
        ];
    }

    /**
     * @param list<mixed> $submittedIds
     * @param array<string, mixed> $submittedPriorities
     * @param array<string, mixed> $primaryVacancy
     * @param list<array<string, mixed>> $compatibleVacancies
     *
     * @return list<array<string, mixed>>
     */
    private function selectedVacancies(
        array $submittedIds,
        array $submittedPriorities,
        array $primaryVacancy,
        array $compatibleVacancies,
    ): array {
        $selectedIds = array_values(array_unique(array_map('intval', $submittedIds)));

        if (!in_array((int) $primaryVacancy['id'], $selectedIds, true)) {
            $selectedIds[] = (int) $primaryVacancy['id'];
        }

        $maximum = min(3, (int) ($primaryVacancy['max_positions'] ?? 3));
        if ($selectedIds === [] || count($selectedIds) > $maximum) {
            throw new DomainException("Pilih minimal satu dan maksimal {$maximum} posisi.");
        }

        $compatibleById = [];
        foreach ($compatibleVacancies as $vacancy) {
            $compatibleById[(int) $vacancy['id']] = $vacancy;
        }

        $selectedVacancies = [];
        foreach ($selectedIds as $selectedId) {
            if (!isset($compatibleById[$selectedId])) {
                throw new DomainException('Terdapat posisi yang tidak termasuk kelompok persyaratan yang sama.');
            }

            $priority = (int) ($submittedPriorities[(string) $selectedId] ?? 0);
            $compatibleById[$selectedId]['preference_order'] = $priority;
            $selectedVacancies[] = $compatibleById[$selectedId];
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
            'skills'              => 'required|min_length[3]|max_length[3000]',
            'work_motivation'     => 'required|min_length[20]|max_length[5000]',
            'career_goal'         => 'required|min_length[20]|max_length[5000]',
            'portfolio_url'       => 'permit_empty|valid_url_strict|max_length[255]',
            'privacy_consent'     => 'required|in_list[1]',
            'profile_photo'       => 'permit_empty|max_size[profile_photo,2048]|ext_in[profile_photo,jpg,jpeg,png]|is_image[profile_photo]',
            'cv'                  => 'uploaded[cv]|max_size[cv,5120]|ext_in[cv,pdf]',
            'document_bundle'     => 'permit_empty|max_size[document_bundle,10240]|ext_in[document_bundle,pdf]',
        ];

        foreach ($questions as $question) {
            if ((int) $question['is_required'] === 1) {
                $rules['screening.' . $question['id']] = 'required|max_length[255]';
            }
        }

        return $rules;
    }
}
