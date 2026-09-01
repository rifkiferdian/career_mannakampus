<?php

namespace App\Modules\Recruitment\Controllers;

use App\Controllers\BaseController;
use App\Modules\Recruitment\Models\ApplicantDocumentModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\StorageSync;
use JsonException;

class StorageSyncController extends BaseController
{
    public function pending(): ResponseInterface
    {
        /** @var StorageSync $config */
        $config = config(StorageSync::class);
        $rows = db_connect()->table('applicant_documents AS documents')
            ->select(
                'documents.id, documents.applicant_id, documents.batch_id, documents.document_type, '
                . 'documents.file_path, documents.original_name, documents.mime_type, documents.file_size, '
                . 'documents.sha256_checksum, documents.created_at, applicants.full_name AS applicant_name, '
                . 'batches.batch_number',
            )
            ->select(
                '(SELECT MIN(applications.application_number) FROM applications '
                . 'WHERE applications.batch_id = documents.batch_id AND applications.deleted_at IS NULL) AS application_number',
                false,
            )
            ->join('applicants', 'applicants.id = documents.applicant_id')
            ->join('application_batches AS batches', 'batches.id = documents.batch_id')
            ->where('documents.document_type', 'application_bundle')
            ->where('documents.local_transfer_status', 'pending')
            ->where('documents.hosting_deleted_at', null)
            ->orderBy('documents.created_at', 'ASC')
            ->orderBy('documents.id', 'ASC')
            ->limit($config->pendingLimit)
            ->get()->getResultArray();

        $documents = [];
        $model = new ApplicantDocumentModel();
        foreach ($rows as $row) {
            $path = $this->resolveUploadPath((string) $row['file_path']);
            if ($path === null || ! $this->isValidPdf($path, $config->maxFileSize)) {
                log_message('error', '[Storage Sync] Dokumen pending tidak dapat dibaca atau bukan PDF valid. Document ID: {id}', [
                    'id' => (int) $row['id'],
                ]);
                continue;
            }

            $actualSize = filesize($path);
            if ($actualSize === false || (int) $row['file_size'] !== $actualSize) {
                log_message('error', '[Storage Sync] Ukuran dokumen tidak sesuai metadata. Document ID: {id}', [
                    'id' => (int) $row['id'],
                ]);
                continue;
            }

            $checksum = hash_file('sha256', $path);
            if (! is_string($checksum)) {
                continue;
            }
            if ($row['sha256_checksum'] !== null && ! hash_equals((string) $row['sha256_checksum'], $checksum)) {
                log_message('error', '[Storage Sync] Checksum dokumen berubah. Document ID: {id}', [
                    'id' => (int) $row['id'],
                ]);
                continue;
            }
            if ($row['sha256_checksum'] === null) {
                $model->update((int) $row['id'], ['sha256_checksum' => $checksum]);
            }

            $documents[] = [
                'id' => (int) $row['id'],
                'applicant_id' => (int) $row['applicant_id'],
                'batch_id' => (int) $row['batch_id'],
                'batch_number' => (string) $row['batch_number'],
                'application_number' => $row['application_number'] !== null ? (string) $row['application_number'] : null,
                'applicant_name' => (string) $row['applicant_name'],
                'document_type' => (string) $row['document_type'],
                'original_filename' => (string) $row['original_name'],
                'mime_type' => 'application/pdf',
                'file_size' => $actualSize,
                'sha256_checksum' => $checksum,
                'uploaded_at' => (string) $row['created_at'],
            ];
        }

        log_message('info', '[Storage Sync] Daftar pending diberikan. Client: {client}; Count: {count}; IP: {ip}', [
            'client' => $this->request->getHeaderLine('X-Sync-Client'),
            'count' => count($documents),
            'ip' => $this->request->getIPAddress(),
        ]);

        return $this->json(['status' => 'success', 'documents' => $documents]);
    }

    public function download(int $id): ResponseInterface
    {
        $document = $this->findDocument($id);
        if ($document === null) {
            return $this->jsonError(404, 'Document not found.');
        }

        /** @var StorageSync $config */
        $config = config(StorageSync::class);
        $path = $this->resolveUploadPath((string) $document['file_path']);
        if ($path === null || ! $this->isValidPdf($path, $config->maxFileSize)) {
            return $this->jsonError(404, 'Document file is unavailable.');
        }

        $size = filesize($path);
        if ($size === false || $size !== (int) $document['file_size']) {
            return $this->jsonError(409, 'Document integrity validation failed.');
        }
        $checksum = hash_file('sha256', $path);
        if (! is_string($checksum)
            || ($document['sha256_checksum'] !== null && ! hash_equals((string) $document['sha256_checksum'], $checksum))) {
            return $this->jsonError(409, 'Document integrity validation failed.');
        }
        if ($document['sha256_checksum'] === null) {
            (new ApplicantDocumentModel())->update($id, ['sha256_checksum' => $checksum]);
        }

        $contents = file_get_contents($path);
        if (! is_string($contents)) {
            return $this->jsonError(500, 'Document could not be read.');
        }

        log_message('info', '[Storage Sync] Dokumen diunduh. Client: {client}; Document ID: {id}; Bytes: {bytes}; IP: {ip}', [
            'client' => $this->request->getHeaderLine('X-Sync-Client'),
            'id' => $id,
            'bytes' => $size,
            'ip' => $this->request->getIPAddress(),
        ]);

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Length', (string) $size)
            ->setHeader('Content-Disposition', 'attachment; filename="document-' . $id . '.pdf"')
            ->setHeader('X-Checksum-SHA256', $checksum)
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setBody($contents);
    }

    public function confirm(int $id): ResponseInterface
    {
        try {
            $payload = json_decode((string) ($this->request->getBody() ?? ''), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->jsonError(400, 'Invalid JSON body.');
        }
        if (! is_array($payload)) {
            return $this->jsonError(400, 'Invalid JSON body.');
        }

        $confirmedChecksum = mb_strtolower(trim((string) ($payload['sha256_checksum'] ?? '')));
        $confirmedSize = $payload['file_size'] ?? null;
        if (preg_match('/^[a-f0-9]{64}$/', $confirmedChecksum) !== 1
            || ! is_int($confirmedSize) || $confirmedSize < 1) {
            return $this->jsonError(422, 'Checksum and file size are required.');
        }

        $document = $this->findDocument($id);
        if ($document === null) {
            return $this->jsonError(404, 'Document not found.');
        }
        /** @var StorageSync $config */
        $config = config(StorageSync::class);
        $path = $this->resolveUploadPath((string) $document['file_path']);
        if ($path === null || ! $this->isValidPdf($path, $config->maxFileSize)) {
            return $this->jsonError(404, 'Document file is unavailable.');
        }

        $actualSize = filesize($path);
        $actualChecksum = hash_file('sha256', $path);
        if ($actualSize === false || ! is_string($actualChecksum)
            || $actualSize !== $confirmedSize
            || ! hash_equals($actualChecksum, $confirmedChecksum)
            || ($document['sha256_checksum'] !== null && ! hash_equals((string) $document['sha256_checksum'], $actualChecksum))) {
            log_message('warning', '[Storage Sync] Konfirmasi ditolak karena integritas tidak cocok. Document ID: {id}; IP: {ip}', [
                'id' => $id,
                'ip' => $this->request->getIPAddress(),
            ]);

            return $this->jsonError(422, 'Confirmed file integrity does not match the hosting file.');
        }

        $now = date('Y-m-d H:i:s');
        (new ApplicantDocumentModel())->update($id, [
            'sha256_checksum' => $actualChecksum,
            'local_transfer_status' => 'confirmed',
            'local_transferred_at' => $document['local_transferred_at'] ?? $now,
            'local_confirmed_checksum' => $confirmedChecksum,
            'local_confirmed_size' => $confirmedSize,
        ]);

        log_message('info', '[Storage Sync] Dokumen dikonfirmasi tersimpan lokal. Client: {client}; Document ID: {id}; IP: {ip}', [
            'client' => $this->request->getHeaderLine('X-Sync-Client'),
            'id' => $id,
            'ip' => $this->request->getIPAddress(),
        ]);

        return $this->json([
            'status' => 'success',
            'message' => 'Document confirmed as stored on the local server.',
            'data' => [
                'document_id' => $id,
                'transfer_status' => 'confirmed',
                'local_transferred_at' => $document['local_transferred_at'] ?? $now,
            ],
        ]);
    }

    /** @return array<string, mixed>|null */
    private function findDocument(int $id): ?array
    {
        $row = db_connect()->table('applicant_documents')
            ->where('id', $id)
            ->where('document_type', 'application_bundle')
            ->where('hosting_deleted_at', null)
            ->get()->getRowArray();

        return $row !== null ? $row : null;
    }

    private function resolveUploadPath(string $relativePath): ?string
    {
        $root = realpath(WRITEPATH . 'uploads');
        if ($root === false) {
            return null;
        }
        $normalized = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $path = realpath($root . DIRECTORY_SEPARATOR . $normalized);
        if ($path === false || ! is_file($path)
            || ! str_starts_with(mb_strtolower($path), mb_strtolower($root . DIRECTORY_SEPARATOR))) {
            return null;
        }

        return $path;
    }

    private function isValidPdf(string $path, int $maxFileSize): bool
    {
        $size = filesize($path);
        if ($size === false || $size < 8 || $size > $maxFileSize) {
            return false;
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $signature = fread($handle, 5);
        fclose($handle);

        return $signature === '%PDF-';
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($payload);
    }

    private function jsonError(int $status, string $message): ResponseInterface
    {
        return $this->json(['status' => 'error', 'message' => $message], $status);
    }
}
