<?php

namespace App\Modules\Recruitment\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

class VacancyController extends BaseController
{
    public function index(): string
    {
        try {
            $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100);
            $data = Services::vacancyCatalog()->catalogPageData();

            if ($keyword !== '') {
                $data['vacancies'] = Services::vacancyCatalog()->searchOpenVacancies($keyword);
            }

            $data['keyword'] = $keyword;

            return view('jobs', $data);
        } catch (Throwable $exception) {
            log_message('error', '[Recruitment] Gagal memuat katalog lowongan: {message}', [
                'message'   => $exception->getMessage(),
                'exception' => $exception,
            ]);

            if (ENVIRONMENT !== 'production') {
                throw $exception;
            }

            return view('jobs', [
                'vacancies'   => [],
                'departments' => [],
            ]);
        }
    }

    public function search(): ResponseInterface
    {
        try {
            $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100);
            $department = mb_substr(trim((string) $this->request->getGet('department')), 0, 50);
            $vacancies = Services::vacancyCatalog()->searchOpenVacancies($keyword, $department);

            return $this->response->setJSON([
                'count' => count($vacancies),
                'html'  => view('partials/job_openings', ['vacancies' => $vacancies]),
            ]);
        } catch (Throwable $exception) {
            log_message('error', '[Recruitment] Pencarian lowongan gagal: {message}', [
                'message'   => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON(['message' => 'Pencarian lowongan sedang mengalami kendala.']);
        }
    }
}
