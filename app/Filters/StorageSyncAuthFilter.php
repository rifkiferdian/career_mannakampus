<?php

namespace App\Filters;

use App\Libraries\StorageSyncSignature;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\StorageSync;

class StorageSyncAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        /** @var StorageSync $config */
        $config = config(StorageSync::class);
        if (! $config->isConfigured()) {
            log_message('critical', '[Storage Sync] API ditolak karena client ID atau secret belum dikonfigurasi.');

            return $this->reject(503, 'Storage synchronization is not configured.');
        }
        if ($config->requireHttps && ! $request->isSecure()) {
            return $this->reject(403, 'HTTPS is required.');
        }

        $clientId = trim($request->getHeaderLine('X-Sync-Client'));
        $timestamp = trim($request->getHeaderLine('X-Sync-Timestamp'));
        $nonce = mb_strtolower(trim($request->getHeaderLine('X-Sync-Nonce')));
        $signature = mb_strtolower(trim($request->getHeaderLine('X-Sync-Signature')));

        $bucket = hash('sha256', $request->getIPAddress() . '|' . $clientId);
        if (! service('throttler')->check('storage_sync_' . $bucket, $config->rateLimitPerMinute, MINUTE)) {
            $this->audit($request, 'rate_limited', $clientId);

            return $this->reject(429, 'Too many requests.');
        }

        if (! hash_equals($config->clientId, $clientId)
            || preg_match('/^\d{10}$/', $timestamp) !== 1
            || preg_match('/^[a-f0-9]{32,128}$/', $nonce) !== 1
            || preg_match('/^[a-f0-9]{64}$/', $signature) !== 1) {
            $this->audit($request, 'invalid_headers', $clientId);

            return $this->reject(401, 'Invalid API credentials.');
        }

        if (abs(time() - (int) $timestamp) > $config->clockSkewSeconds) {
            $this->audit($request, 'expired_timestamp', $clientId);

            return $this->reject(401, 'Request timestamp is outside the allowed window.');
        }

        $expected = StorageSyncSignature::sign(
            $config->secret,
            $clientId,
            $request->getMethod(),
            $request->getUri()->getPath(),
            $timestamp,
            $nonce,
            (string) ($request->getBody() ?? ''),
        );
        if (! hash_equals($expected, $signature)) {
            $this->audit($request, 'invalid_signature', $clientId);

            return $this->reject(401, 'Invalid API credentials.');
        }

        $cache = cache();
        $nonceKey = 'storage_sync_nonce_' . hash('sha256', $clientId . '|' . $nonce);
        if ($cache->get($nonceKey) !== null) {
            $this->audit($request, 'replayed_nonce', $clientId);

            return $this->reject(409, 'Request has already been processed.');
        }
        if (! $cache->save($nonceKey, 1, $config->nonceTtlSeconds)) {
            log_message('error', '[Storage Sync] Nonce tidak dapat disimpan ke cache.');

            return $this->reject(503, 'Replay protection is unavailable.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }

    private function reject(int $status, string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(['status' => 'error', 'message' => $message]);
    }

    private function audit(RequestInterface $request, string $reason, string $clientId): void
    {
        log_message('warning', '[Storage Sync] Request ditolak. Reason: {reason}; Client: {client}; IP: {ip}; Path: {path}', [
            'reason' => $reason,
            'client' => $clientId !== '' ? mb_substr($clientId, 0, 80) : '(empty)',
            'ip' => $request->getIPAddress(),
            'path' => $request->getUri()->getPath(),
        ]);
    }
}
