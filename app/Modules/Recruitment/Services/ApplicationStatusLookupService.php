<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Recruitment\Presenters\ApplicationStatusPresenter;
use CodeIgniter\Database\BaseConnection;

class ApplicationStatusLookupService
{
    public function __construct(
        private readonly BaseConnection $database,
        private readonly ApplicationStatusPresenter $presenter,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $nik, string $batchNumber): ?array
    {
        $normalizedNik = preg_replace('/\D+/', '', $nik) ?? '';
        $normalizedBatch = mb_strtoupper(trim($batchNumber));
        $nikHash = hash_hmac('sha256', $normalizedNik, (string) config('Encryption')->key);

        $batch = $this->database->table('application_batches AS batches')
            ->select(
                'batches.id, batches.batch_number, batches.applicant_snapshot, '
                . 'batches.submitted_at',
            )
            ->join('applicants AS applicants', 'applicants.id = batches.applicant_id')
            ->where('batches.batch_number', $normalizedBatch)
            ->where('applicants.nik_hash', $nikHash)
            ->where('applicants.is_active', 1)
            ->where('applicants.deleted_at', null)
            ->get()
            ->getRowArray();

        if ($batch === null) {
            return null;
        }

        $applications = $this->database->table('applications AS applications')
            ->select(
                'applications.application_number, applications.preference_order, '
                . 'applications.application_status, applications.public_message, '
                . 'applications.updated_at, vacancies.title AS vacancy_title, '
                . 'departments.name AS department_name',
            )
            ->join('vacancies AS vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('departments AS departments', 'departments.id = vacancies.department_id', 'left')
            ->where('applications.batch_id', (int) $batch['id'])
            ->where('applications.deleted_at', null)
            ->orderBy('applications.preference_order', 'ASC')
            ->get()
            ->getResultArray();

        if ($applications === []) {
            return null;
        }

        return $this->presenter->present($batch, $applications);
    }
}
