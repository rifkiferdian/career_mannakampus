<?php

namespace App\Modules\Admin\Services;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class ApplicantDeletionService
{
    public function __construct(private BaseConnection $database)
    {
    }

    public function delete(int $applicantId): ?bool
    {
        if (! $this->database->transBegin()) {
            throw new RuntimeException('Gagal memulai transaksi penghapusan.');
        }

        try {
            $table = $this->database->protectIdentifiers('applicants', true);
            $applicant = $this->database->query(
                "SELECT profile_photo_path FROM {$table} WHERE id = ? FOR UPDATE",
                [$applicantId],
            )->getRowArray();
            if ($applicant === null) {
                $this->database->transRollback();

                return null;
            }

            $documents = $this->database->table('applicant_documents')->select('file_path')
                ->where('applicant_id', $applicantId)->get()->getResultArray();
            $batches = $this->database->table('application_batches')->select('applicant_snapshot')
                ->where('applicant_id', $applicantId)->get()->getResultArray();
            $paths = array_column($documents, 'file_path');
            $paths[] = $applicant['profile_photo_path'];
            foreach ($batches as $batch) {
                $snapshot = json_decode((string) $batch['applicant_snapshot'], true);
                $paths[] = $snapshot['profile_photo_path'] ?? null;
            }

            // Restricted children go first. Foreign keys cascade to their dependent records.
            foreach (['applications', 'applicant_documents', 'application_batches'] as $child) {
                $this->database->table($child)->where('applicant_id', $applicantId)->delete();
            }
            $this->database->table('applicants')->where('id', $applicantId)->delete();
            if (! $this->database->transStatus() || ! $this->database->transCommit()) {
                throw new RuntimeException('Transaksi penghapusan gagal.');
            }
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        $root = realpath(WRITEPATH . 'uploads');
        $removed = true;
        foreach (array_unique(array_filter($paths, 'is_string')) as $path) {
            if ($path === '') {
                continue;
            }
            try {
                if ($this->removeUpload($root, $path)) {
                    continue;
                }
            } catch (Throwable) {
                // Keep cleaning the remaining uploads and report partial failure.
            }
            log_message('error', 'Berkas pelamar {id} gagal dibersihkan: {path}', ['id' => $applicantId, 'path' => $path]);
            $removed = false;
        }

        return $removed;
    }

    private function removeUpload(string|false $root, string $path): bool
    {
        $relative = str_replace('\\', '/', $path);
        if (str_starts_with($relative, '/') || preg_match('~(^|/)\.\.?(/|$)|[:\x00]~', $relative)) {
            return false;
        }
        if ($root === false) {
            return ! is_dir(WRITEPATH . 'uploads');
        }

        $candidate = $root . DIRECTORY_SEPARATOR . $relative;
        if (is_link($candidate)) {
            return false;
        }
        $resolved = realpath($candidate);
        if ($resolved === false) {
            return ! file_exists($candidate);
        }

        return str_starts_with($resolved, $root . DIRECTORY_SEPARATOR)
            && is_file($resolved)
            && unlink($resolved);
    }
}
