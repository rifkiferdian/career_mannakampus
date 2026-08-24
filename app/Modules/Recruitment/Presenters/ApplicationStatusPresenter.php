<?php

namespace App\Modules\Recruitment\Presenters;

use DateTimeImmutable;
use Throwable;

class ApplicationStatusPresenter
{
    /**
     * @param array<string, mixed> $batch
     * @param list<array<string, mixed>> $applications
     *
     * @return array<string, mixed>
     */
    public function present(array $batch, array $applications): array
    {
        $snapshot = json_decode((string) ($batch['applicant_snapshot'] ?? ''), true);
        $snapshot = is_array($snapshot) ? $snapshot : [];
        $identity = is_array($snapshot['identity'] ?? null) ? $snapshot['identity'] : [];
        $contact = is_array($snapshot['contact'] ?? null) ? $snapshot['contact'] : [];

        $result = [
            'batch_number'     => (string) $batch['batch_number'],
            'applicant_name'   => $this->maskName((string) ($identity['full_name'] ?? 'Pelamar')),
            'applicant_email'  => $this->maskEmail((string) ($contact['email'] ?? '')),
            'applicant_phone'  => $this->maskPhone((string) ($contact['phone'] ?? '')),
            'submitted_at'     => $this->date((string) $batch['submitted_at']),
            'position_count'   => count($applications),
            'applications'     => array_map(fn (array $application): array => $this->application($application), $applications),
        ];

        return $result;
    }

    /**
     * @param array<string, mixed> $application
     *
     * @return array<string, mixed>
     */
    private function application(array $application): array
    {
        $status = (string) ($application['application_status'] ?? 'submitted');
        [$label, $description, $tone] = $this->status($status);

        $result = [
            'application_number' => (string) $application['application_number'],
            'preference_order'   => (int) $application['preference_order'],
            'vacancy_title'      => (string) $application['vacancy_title'],
            'department_name'    => (string) ($application['department_name'] ?? ''),
            'status_label'       => $label,
            'status_description' => $description,
            'status_tone'        => $tone,
            'public_message'     => trim((string) ($application['public_message'] ?? '')),
            'updated_at'         => $this->date((string) $application['updated_at']),
        ];
        $schedule = $application['schedule'] ?? null;
        if (is_array($schedule)) {
            $result['schedule'] = [
                'id' => (int) $schedule['id'],
                'stage_name' => (string) $schedule['stage_name'],
                'scheduled_at' => $this->date((string) $schedule['scheduled_at']),
                'scheduled_at_raw' => (string) $schedule['scheduled_at'],
                'venue' => (string) $schedule['venue'],
                'instructions' => trim((string) ($schedule['instructions'] ?? '')),
                'confirmation_deadline' => $this->date((string) $schedule['confirmation_deadline_at']),
                'confirmation_deadline_raw' => (string) $schedule['confirmation_deadline_at'],
                'status' => (string) $schedule['status'],
                'candidate_note' => trim((string) ($schedule['candidate_note'] ?? '')),
                'pic_name' => (string) $schedule['pic_name'],
            ];
        }

        return $result;
    }

    /**
     * @return array{string, string, string}
     */
    private function status(string $status): array
    {
        return match ($status) {
            'lamaran_baru' => [
                'Lamaran Baru',
                'Data lamaran sudah tersimpan dan menunggu screening manual oleh tim HRD.',
                'neutral',
            ],
            'document_screening' => [
                'Sedang Screening',
                'Tim HRD sedang memeriksa profil, dokumen, dan jawaban screening Anda.',
                'progress',
            ],
            'screening_passed' => [
                'Lolos Screening',
                'Lamaran telah dinyatakan lolos screening oleh tim HRD.',
                'success',
            ],
            'screening_failed' => [
                'Tidak Lolos Screening',
                'Lamaran belum dapat dilanjutkan untuk posisi ini.',
                'danger',
            ],
            'under_review', 'reviewed' => [
                'Sedang ditinjau',
                'Tim rekrutmen sedang meninjau profil dan dokumen Anda.',
                'progress',
            ],
            'test_scheduled', 'assessment' => [
                'Tahap tes',
                'Lamaran telah masuk ke tahap tes atau asesmen.',
                'progress',
            ],
            'interview_scheduled', 'interview_hr', 'interview_user' => [
                'Tahap interview',
                'Lamaran telah masuk ke tahap interview. Periksa email dan WhatsApp secara berkala.',
                'progress',
            ],
            'accepted', 'hired' => [
                'Diterima',
                'Selamat, proses rekrutmen untuk posisi ini dinyatakan berhasil.',
                'success',
            ],
            'rejected' => [
                'Belum berhasil',
                'Proses lamaran untuk posisi ini telah selesai dan belum dapat dilanjutkan.',
                'danger',
            ],
            'withdrawn' => [
                'Dibatalkan',
                'Lamaran untuk posisi ini telah dibatalkan.',
                'neutral',
            ],
            default => [
                'Lamaran diterima',
                'Data lamaran sudah tersimpan dan menunggu proses berikutnya.',
                'neutral',
            ],
        };
    }

    private function maskName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return implode(' ', array_map(static function (string $part, int $index): string {
            $part = trim($part, " \t\n\r\0\x0B()[]");
            if ($part === '') {
                return '';
            }
            if ($index === 0) {
                return $part;
            }

            return mb_substr($part, 0, 1) . str_repeat('*', max(2, mb_strlen($part) - 1));
        }, $parts, array_keys($parts)));
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible . str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))) . '@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        }

        if (strlen($digits) < 7) {
            return '';
        }

        return substr($digits, 0, 4)
            . str_repeat('*', strlen($digits) - 6)
            . substr($digits, -2);
    }

    private function date(string $date): string
    {
        try {
            return (new DateTimeImmutable($date))->format('d M Y, H:i');
        } catch (Throwable) {
            return '-';
        }
    }
}
