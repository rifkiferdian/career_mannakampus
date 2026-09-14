<?php

namespace App\Modules\Recruitment\Controllers;

use App\Controllers\BaseController;
use App\Modules\Recruitment\Services\IndonesiaRegionService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;

class RegionController extends BaseController
{
    public function index(string $level): ResponseInterface
    {
        $parent = $this->request->getGet('parent');
        try {
            $rows = (new IndonesiaRegionService(db_connect()))->options($level, is_string($parent) ? $parent : '');
        } catch (DomainException $exception) {
            return $this->response->setStatusCode(400)->setJSON(['message' => $exception->getMessage()]);
        }

        return $this->response->setJSON(['regions' => $rows]);
    }
}
