<?php

namespace App\Modules\Recruitment\Services;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use InvalidArgumentException;

class RecruitmentScheduleService
{
    public const ACTIVE_STATUSES = ['scheduled', 'confirmed', 'reschedule_requested'];
    public const STATUSES = ['scheduled', 'confirmed', 'reschedule_requested', 'present', 'absent', 'cancelled'];

    public function __construct(private readonly BaseConnection $database)
    {
    }

    /** @param array<string, mixed> $input */
    public function validateInput(array $input, ?int $ignoreScheduleId = null): array
    {
        $scheduledAt = $this->dateTime((string) ($input['scheduled_at'] ?? ''));
        $deadline = $this->dateTime((string) ($input['confirmation_deadline_at'] ?? ''));
        $venue = mb_substr(trim((string) ($input['venue'] ?? '')), 0, 1000);
        $picUserId = (int) ($input['pic_user_id'] ?? 0);
        if ($scheduledAt === null || $scheduledAt <= new DateTimeImmutable()) {
            throw new InvalidArgumentException('Tanggal dan jam pelaksanaan harus berada di masa mendatang.');
        }
        if ($deadline === null || $deadline <= new DateTimeImmutable() || $deadline >= $scheduledAt) {
            throw new InvalidArgumentException('Batas konfirmasi harus berada di masa mendatang dan sebelum jadwal pelaksanaan.');
        }
        if ($venue === '') {
            throw new InvalidArgumentException('Lokasi atau link meeting wajib diisi.');
        }
        if ($picUserId < 1 || $this->database->table('users')->where('id', $picUserId)->where('is_active', 1)->countAllResults() === 0) {
            throw new InvalidArgumentException('PIC/interviewer tidak valid atau sudah tidak aktif.');
        }
        $conflict = $this->database->table('recruitment_schedules')
            ->where('pic_user_id', $picUserId)
            ->where('scheduled_at', $scheduledAt->format('Y-m-d H:i:s'))
            ->whereIn('status', self::ACTIVE_STATUSES);
        if ($ignoreScheduleId !== null) {
            $conflict->where('id !=', $ignoreScheduleId);
        }
        if ($conflict->countAllResults() > 0) {
            throw new InvalidArgumentException('PIC sudah memiliki jadwal lain pada tanggal dan jam yang sama.');
        }

        return [
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'venue' => $venue,
            'pic_user_id' => $picUserId,
            'instructions' => mb_substr(trim((string) ($input['instructions'] ?? '')), 0, 5000) ?: null,
            'confirmation_deadline_at' => $deadline->format('Y-m-d H:i:s'),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(int $applicationId, int $stageId, array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $this->database->table('recruitment_schedules')->insert($data + [
            'application_id' => $applicationId,
            'stage_id' => $stageId,
            'status' => 'scheduled',
            'candidate_note' => null,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $scheduleId = (int) $this->database->insertID();
        $this->record($scheduleId, 'created', 'Jadwal seleksi dibuat.', $userId);

        return $scheduleId;
    }

    /** @param array<string, mixed> $data */
    public function update(int $scheduleId, array $data, int $userId): void
    {
        $this->database->table('recruitment_schedules')->where('id', $scheduleId)->update($data + [
            'status' => 'scheduled',
            'candidate_note' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->record($scheduleId, 'rescheduled', 'Jadwal diperbarui dan menunggu konfirmasi ulang kandidat.', $userId);
    }

    /** @param array<string, mixed> $data */
    public function rescheduleAfterAbsence(int $sourceScheduleId, array $data, int $userId, string $reason): int
    {
        $source = $this->find($sourceScheduleId);
        if ($source === null || (string) $source['status'] !== 'absent') {
            throw new InvalidArgumentException('Hanya jadwal berstatus Tidak hadir yang dapat dijadwalkan ulang.');
        }
        if ($this->database->table('recruitment_schedules')
            ->where('application_id', (int) $source['application_id'])
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->countAllResults() > 0) {
            throw new InvalidArgumentException('Pelamar sudah memiliki jadwal aktif. Muat ulang halaman untuk melihat jadwal terbaru.');
        }

        $reason = mb_substr(trim($reason), 0, 1000);
        if (mb_strlen($reason) < 5) {
            throw new InvalidArgumentException('Alasan penjadwalan ulang minimal 5 karakter.');
        }

        $this->database->transStart();
        $newScheduleId = $this->create((int) $source['application_id'], (int) $source['stage_id'], $data, $userId);
        $this->record($sourceScheduleId, 'reschedule_created', 'Dibuat jadwal ulang #' . $newScheduleId . '. Alasan: ' . $reason, $userId);
        $this->record($newScheduleId, 'rescheduled_after_absence', 'Jadwal ulang dari jadwal #' . $sourceScheduleId . '. Alasan: ' . $reason, $userId);
        $this->database->transComplete();
        if (! $this->database->transStatus()) {
            throw new InvalidArgumentException('Jadwal ulang gagal disimpan. Silakan coba kembali.');
        }

        return $newScheduleId;
    }

    public function setStatus(int $scheduleId, string $status, ?int $userId, ?string $notes = null): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Status jadwal tidak valid.');
        }
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'reschedule_requested') {
            $data['candidate_note'] = mb_substr(trim((string) $notes), 0, 2000) ?: null;
        }
        $this->database->table('recruitment_schedules')->where('id', $scheduleId)->update($data);
        $labels = [
            'confirmed' => 'Kandidat mengonfirmasi akan hadir.',
            'reschedule_requested' => 'Kandidat meminta jadwal ulang.',
            'present' => 'Kandidat dicatat hadir.',
            'absent' => 'Kandidat dicatat tidak hadir.',
            'cancelled' => 'Jadwal dibatalkan.',
        ];
        $this->record($scheduleId, $status, $notes ?: ($labels[$status] ?? 'Status jadwal diperbarui.'), $userId);
    }

    /** @return array<string, mixed>|null */
    public function find(int $scheduleId): ?array
    {
        return $this->database->table('recruitment_schedules')->where('id', $scheduleId)->get()->getRowArray() ?: null;
    }

    public function cancelActiveForApplication(int $applicationId, int $userId, string $notes): void
    {
        $rows = $this->database->table('recruitment_schedules')->select('id')->where('application_id', $applicationId)->whereIn('status', self::ACTIVE_STATUSES)->get()->getResultArray();
        foreach ($rows as $row) {
            $this->setStatus((int) $row['id'], 'cancelled', $userId, $notes);
        }
    }

    private function record(int $scheduleId, string $action, string $notes, ?int $userId): void
    {
        $this->database->table('recruitment_schedule_histories')->insert([
            'schedule_id' => $scheduleId,
            'action' => $action,
            'notes' => mb_substr(trim($notes), 0, 5000) ?: null,
            'changed_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function dateTime(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value)
            ?: DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);

        return $date !== false ? $date : null;
    }
}
