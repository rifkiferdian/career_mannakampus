<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class StorageSync extends BaseConfig
{
    public string $clientId = '';
    public string $secret = '';
    public int $clockSkewSeconds = 300;
    public int $nonceTtlSeconds = 600;
    public int $rateLimitPerMinute = 120;
    public int $pendingLimit = 100;
    public int $maxFileSize = 5_242_880;
    public bool $requireHttps = true;

    public function __construct()
    {
        parent::__construct();

        $this->clientId = trim((string) env('storageSync.clientId', ''));
        $this->secret = trim((string) env('storageSync.secret', ''));
        $this->clockSkewSeconds = max(30, (int) env('storageSync.clockSkewSeconds', 300));
        $this->nonceTtlSeconds = max($this->clockSkewSeconds, (int) env('storageSync.nonceTtlSeconds', 600));
        $this->rateLimitPerMinute = max(10, (int) env('storageSync.rateLimitPerMinute', 120));
        $this->pendingLimit = min(500, max(1, (int) env('storageSync.pendingLimit', 100)));
        $this->maxFileSize = max(1_048_576, (int) env('storageSync.maxFileSize', 5_242_880));
        $this->requireHttps = filter_var(env('storageSync.requireHttps', true), FILTER_VALIDATE_BOOL);
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && strlen($this->secret) >= 32;
    }
}
