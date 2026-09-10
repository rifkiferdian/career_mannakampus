<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class LocalFileServer extends BaseConfig
{
    public string $baseUrl = '';

    public function __construct()
    {
        parent::__construct();

        $this->baseUrl = rtrim(trim((string) env('localFileServer.baseUrl', '')), '/') . '/';
        if ($this->baseUrl === '/') {
            $this->baseUrl = '';
        }
    }

    public function documentUrl(int $remoteDocumentId): ?string
    {
        if ($this->baseUrl === '' || $remoteDocumentId < 1) {
            return null;
        }

        return $this->baseUrl . 'dokumen/hosting/' . $remoteDocumentId . '/buka';
    }
}
