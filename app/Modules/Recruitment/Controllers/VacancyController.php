<?php

namespace App\Modules\Recruitment\Controllers;

use App\Controllers\BaseController;
use Config\Services;
use Throwable;

class VacancyController extends BaseController
{
    public function index(): string
    {
        try {
            return view('jobs', Services::vacancyCatalog()->catalogPageData());
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
}
