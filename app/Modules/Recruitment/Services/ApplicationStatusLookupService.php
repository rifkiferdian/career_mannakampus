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
    public function find(string $nik): ?array
    {
        $normalizedNik = preg_replace('/\D+/', '', $nik) ?? '';
        $nikHash = hash_hmac('sha256', $normalizedNik, (string) config('Encryption')->key);

        $batches = $this->database->table('application_batches AS batches')
            ->select(
                'batches.id, batches.batch_number, batches.applicant_snapshot, '
                . 'batches.submitted_at',
            )
            ->join('applicants AS applicants', 'applicants.id = batches.applicant_id')
            ->where('applicants.nik_hash', $nikHash)
            ->where('applicants.is_active', 1)
            ->where('applicants.deleted_at', null)
            ->orderBy('batches.submitted_at', 'DESC')
            ->orderBy('batches.id', 'DESC')
            ->get()
            ->getResultArray();

        if ($batches === []) {
            return null;
        }

        $batchIds = array_map('intval', array_column($batches, 'id'));

        $applications = $this->database->table('applications AS applications')
            ->select(
                'applications.batch_id, applications.application_number, applications.preference_order, '
                . 'applications.application_status, applications.public_message, '
                . 'applications.updated_at, vacancies.title AS vacancy_title, '
                . 'departments.name AS department_name',
            )
            ->join('vacancies AS vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('departments AS departments', 'departments.id = vacancies.department_id', 'left')
            ->whereIn('applications.batch_id', $batchIds)
            ->where('applications.deleted_at', null)
            ->orderBy('applications.submitted_at', 'DESC')
            ->orderBy('applications.preference_order', 'ASC')
            ->get()
            ->getResultArray();

        if ($applications === []) {
            return null;
        }

        $applicationsByBatch = [];
        foreach ($applications as $application) {
            $applicationsByBatch[(int) $application['batch_id']][] = $application;
        }

        $result = null;
        $allApplications = [];
        $batchCount = 0;
        foreach ($batches as $batch) {
            $batchApplications = $applicationsByBatch[(int) $batch['id']] ?? [];
            if ($batchApplications === []) {
                continue;
            }

            $presented = $this->presenter->present($batch, $batchApplications);
            $result ??= $presented;
            $batchCount++;
            foreach ($presented['applications'] as $application) {
                $application['batch_number'] = $presented['batch_number'];
                $application['batch_submitted_at'] = $presented['submitted_at'];
                $allApplications[] = $application;
            }
        }

        if ($result === null || $allApplications === []) {
            return null;
        }

        $result['batch_count'] = $batchCount;
        $result['position_count'] = count($allApplications);
        $result['applications'] = $allApplications;

        return $result;
    }
}
