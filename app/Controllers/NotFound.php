<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class NotFound extends BaseController
{
    public function index(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(404)
            ->setBody(view('errors/html/error_404'));
    }
}
