<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');

        $database = db_connect();

        return view('admin/dashboard', [
            'auth'               => session()->get('hrd_auth'),
            'openVacancies'      => $this->countOpenVacancies($database),
            'applicantCount'     => $database->table('applicants')->where('deleted_at', null)->countAllResults(),
            'applicationCount'   => $database->table('application_batches')->countAllResults(),
            'recentApplications' => $this->recentApplications($database),
            'success'            => session()->getFlashdata('auth_success'),
            'error'              => session()->getFlashdata('access_error'),
        ]);
    }

    private function countOpenVacancies(BaseConnection $database): int
    {
        return $database->table('vacancies')
            ->where('status', 'open')
            ->where('deleted_at', null)
            ->countAllResults();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentApplications(BaseConnection $database): array
    {
        return $database->table('application_batches AS batches')
            ->select('batches.batch_number, batches.position_count, batches.submitted_at, applicants.full_name, applicants.email')
            ->join('applicants', 'applicants.id = batches.applicant_id')
            ->orderBy('batches.submitted_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
    }
}
