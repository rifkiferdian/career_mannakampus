<?php

namespace App\Controllers;

use Config\Services;
use Throwable;

class Home extends BaseController
{
    public function index(): string
    {
        try {
            $vacancies = Services::vacancyCatalog()->openVacancies(3);
        } catch (Throwable $exception) {
            log_message('error', '[Recruitment] Gagal memuat lowongan beranda: {message}', [
                'message'   => $exception->getMessage(),
                'exception' => $exception,
            ]);

            if (ENVIRONMENT !== 'production') {
                throw $exception;
            }

            $vacancies = [];
        }

        return view('welcome_message', ['vacancies' => $vacancies]);
    }

    public function selectionProcess(): string
    {
        return view('selection_process');
    }

}
