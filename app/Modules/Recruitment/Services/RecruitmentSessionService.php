<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Admin\Services\AuthorizationService;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

class RecruitmentSessionService
{
    public const STATUSES = ['draft' => 'Draf', 'scheduled' => 'Terjadwal', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
    public const PARTICIPANT_STATUSES = ['scheduled' => 'Menunggu konfirmasi', 'confirmed' => 'Terkonfirmasi', 'reschedule_requested' => 'Minta jadwal ulang', 'present' => 'Hadir', 'absent' => 'Tidak hadir', 'cancelled' => 'Dibatalkan'];

    private AuthorizationService $authorization;
    private RecruitmentScheduleService $schedules;

    public function __construct(private readonly BaseConnection $db)
    {
        $this->authorization = new AuthorizationService($db);
        $this->schedules = new RecruitmentScheduleService($db);
    }

    public function can(int $userId, string $permission): bool
    {
        return $this->authorization->can($userId, $permission);
    }

    /** PIC, creator and colleagues in the PIC's team may access the session. */
    public function visibleSessions(int $userId): BaseBuilder
    {
        $query = $this->db->table('recruitment_sessions AS sessions');
        if (! $this->can($userId, 'schedules.view_all')) {
            $query->groupStart()->where('sessions.created_by', $userId)
                ->orWhereIn('sessions.pic_user_id', $this->picIds($userId))->groupEnd();
        }

        return $query;
    }

    public function find(int $id, int $userId): array
    {
        $row = $this->visibleSessions($userId)->select('sessions.*, stages.name AS stage_name, stages.code AS stage_code, pic.full_name AS pic_name, creator.full_name AS created_by_name')
            ->join('recruitment_stages AS stages', 'stages.id = sessions.stage_id')
            ->join('users AS pic', 'pic.id = sessions.pic_user_id')
            ->join('users AS creator', 'creator.id = sessions.created_by')
            ->where('sessions.id', $id)->get()->getRowArray();
        if ($row === null) {
            throw new InvalidArgumentException('Agenda tidak ditemukan atau tidak dapat Anda akses.');
        }

        return $row;
    }

    public function pics(int $userId): array
    {
        $query = $this->db->table('users')->select('id, full_name')->where('is_active', 1)->where('deleted_at', null);
        if (! $this->can($userId, 'schedules.view_all')) {
            $query->whereIn('id', $this->picIds($userId));
        }

        return $query->orderBy('full_name')->get()->getResultArray();
    }

    public function eligible(array $session, int $userId): BaseBuilder
    {
        $now = $this->db->escape(date('Y-m-d H:i:s'));
        $query = $this->db->table('applications AS applications')
            ->select('applications.id, applications.application_number, applicants.id AS applicant_id, applicants.full_name, vacancies.title AS vacancy_title')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('recruitment_schedules AS member', 'member.application_id = applications.id AND member.session_id = ' . (int) $session['id'], 'left')
            ->join('applicant_blacklists AS blacklist', 'blacklist.applicant_id = applicants.id AND blacklist.revoked_at IS NULL AND blacklist.starts_at <= ' . $now . ' AND (blacklist.is_permanent = 1 OR blacklist.ends_at >= ' . $now . ')', 'left', false)
            ->where('applications.deleted_at', null)->where('applicants.deleted_at', null)->where('vacancies.deleted_at', null)
            ->where('applications.application_status', $session['stage_code'])
            ->where('member.id', null)->where('blacklist.id', null);
        if (! $this->can($userId, 'schedules.view_all')) {
            $query->whereIn('applicants.assigned_hrd_team_id', $this->teamIds($userId));
        }

        return $query;
    }

    public function participants(int $id): BaseBuilder
    {
        return $this->db->table('recruitment_schedules AS schedules')
            ->select('schedules.*, applicants.id AS applicant_id, applicants.full_name, applicants.assigned_hrd_team_id, applications.application_number, vacancies.title AS vacancy_title')
            ->join('applications', 'applications.id = schedules.application_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->where('schedules.session_id', $id)->where('applications.deleted_at', null)->where('applicants.deleted_at', null);
    }

    public function save(array $input, int $userId, ?int $id = null): int
    {
        $this->requirePermission($userId, 'schedules.manage');

        return $this->transaction(function () use ($input, $userId, $id): int {
            $existing = null;
            if ($id !== null) {
                $this->lock('recruitment_sessions', $id);
                $existing = $this->find($id, $userId);
                $this->requireEditable($existing, true);
            }
            $name = trim((string) ($input['name'] ?? ''));
            $venue = trim((string) ($input['venue'] ?? ''));
            $start = self::parseDate((string) ($input['starts_at'] ?? ''));
            $end = trim((string) ($input['ends_at'] ?? '')) === '' ? null : self::parseDate((string) $input['ends_at']);
            $stageId = (int) ($input['stage_id'] ?? 0);
            $pic = (int) ($input['pic_user_id'] ?? 0);
            $capacity = trim((string) ($input['capacity'] ?? ''));
            $status = (string) ($input['status'] ?? 'draft');
            if ($name === '' || mb_strlen($name) > 200 || $venue === '' || mb_strlen($venue) > 1000) {
                throw new InvalidArgumentException('Nama agenda (maksimal 200 karakter) dan lokasi (maksimal 1000 karakter) wajib diisi.');
            }
            $keepsStartedTime = $existing !== null
                && $existing['starts_at'] <= date('Y-m-d H:i:s')
                && $start === $existing['starts_at'];
            if (($start <= date('Y-m-d H:i:s') && ! $keepsStartedTime) || ($end !== null && $end <= $start)) {
                throw new InvalidArgumentException('Waktu mulai harus di masa mendatang dan waktu selesai harus setelah waktu mulai.');
            }
            if ($capacity !== '' && (! ctype_digit($capacity) || (int) $capacity < 1 || (int) $capacity > 100000)) {
                throw new InvalidArgumentException('Kuota harus antara 1 dan 100.000 atau dikosongkan.');
            }
            if (! in_array($status, ['draft', 'scheduled'], true)) {
                throw new InvalidArgumentException('Status agenda tidak valid.');
            }
            if ($this->db->table('recruitment_stages')->where('id', $stageId)->where('is_active', 1)->where('is_schedulable', 1)->countAllResults() === 0) {
                throw new InvalidArgumentException('Pilih tahap seleksi aktif yang dapat dijadwalkan.');
            }
            if (! in_array($pic, array_map('intval', array_column($this->pics($userId), 'id')), true)) {
                throw new InvalidArgumentException('PIC tidak aktif atau di luar cakupan tim Anda.');
            }
            $this->lock('users', $pic);
            $members = $id === null ? [] : $this->db->table('recruitment_schedules')->where('session_id', $id)->get()->getResultArray();
            if ($members !== [] && ($stageId !== (int) $existing['stage_id'] || $status !== 'scheduled')) {
                throw new InvalidArgumentException('Agenda yang memiliki peserta harus tetap terjadwal dan tahap seleksinya tidak dapat diganti.');
            }
            $active = array_filter($members, static fn (array $row): bool => $row['status'] !== 'cancelled');
            if ($capacity !== '' && count($active) > (int) $capacity) {
                throw new InvalidArgumentException('Kuota lebih kecil daripada jumlah peserta aktif.');
            }
            $data = ['name' => $name, 'stage_id' => $stageId, 'starts_at' => $start, 'ends_at' => $end, 'venue' => $venue,
                'pic_user_id' => $pic, 'capacity' => $capacity === '' ? null : (int) $capacity,
                'instructions' => mb_substr(trim((string) ($input['instructions'] ?? '')), 0, 5000) ?: null,
                'status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            $this->checkPicConflict($data, $id, [], $members !== []);
            $scheduleChanged = $existing !== null && ($start !== $existing['starts_at'] || $venue !== $existing['venue']
                || $pic !== (int) $existing['pic_user_id'] || $data['instructions'] !== $existing['instructions']);
            if ($existing !== null && $end !== $existing['ends_at']) {
                foreach ($active as $member) {
                    $this->checkApplicantConflict((int) $member['application_id'], $start, $id, $end);
                }
            }
            if ($scheduleChanged) {
                $deadline = $active === [] ? null : $this->deadline((string) ($input['confirmation_deadline_at'] ?? ''), $start);
                foreach ($active as $member) {
                    if (! in_array($member['status'], RecruitmentScheduleService::ACTIVE_STATUSES, true)) {
                        throw new InvalidArgumentException('Jadwal agenda tidak dapat diubah karena sudah ada kehadiran yang dicatat.');
                    }
                    $this->checkApplicantConflict((int) $member['application_id'], $start, $id, $end);
                    $this->schedules->update((int) $member['id'], ['scheduled_at' => $start, 'venue' => $venue, 'pic_user_id' => $pic,
                        'instructions' => $data['instructions'], 'confirmation_deadline_at' => $deadline], $userId);
                }
            }
            if ($id === null) {
                $this->db->table('recruitment_sessions')->insert($data + ['created_by' => $userId, 'created_at' => date('Y-m-d H:i:s')]);

                return (int) $this->db->insertID();
            }
            $this->db->table('recruitment_sessions')->where('id', $id)->update($data);

            return $id;
        });
    }

    public function addParticipants(int $id, array $ids, string $deadline, int $userId): int
    {
        $this->requirePermission($userId, 'schedules.manage');
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        if ($ids === [] || count($ids) > 50 || min($ids) < 1) {
            throw new InvalidArgumentException('Pilih 1 sampai 50 pelamar dalam satu penambahan.');
        }

        return $this->transaction(function () use ($id, $ids, $deadline, $userId): int {
            $this->lock('recruitment_sessions', $id);
            $session = $this->find($id, $userId);
            $this->requireEditable($session, true);
            if ($session['status'] !== 'scheduled') {
                throw new InvalidArgumentException('Ubah status agenda menjadi Terjadwal sebelum menambahkan peserta.');
            }
            $deadline = $session['starts_at'] <= date('Y-m-d H:i:s')
                ? $session['starts_at']
                : $this->deadline($deadline, $session['starts_at']);
            $this->lock('users', (int) $session['pic_user_id']);
            if ($this->db->table('users')->where('id', $session['pic_user_id'])->where('is_active', 1)->where('deleted_at', null)->countAllResults() === 0) {
                throw new InvalidArgumentException('PIC agenda sudah tidak aktif. Ubah PIC sebelum menambahkan peserta.');
            }
            if ($this->db->table('recruitment_stages')->where('id', $session['stage_id'])->where('is_active', 1)->where('is_schedulable', 1)->countAllResults() === 0) {
                throw new InvalidArgumentException('Tahap seleksi agenda sudah tidak aktif.');
            }
            foreach ($ids as $applicationId) {
                $this->lock('applications', $applicationId);
            }
            $eligible = $this->eligible($session, $userId)->whereIn('applications.id', $ids)->get()->getResultArray();
            if (count($eligible) !== count($ids)) {
                throw new InvalidArgumentException('Ada pelamar yang sudah terdaftar, berbeda tahap, terkena blacklist, atau di luar tim Anda. Muat ulang daftar peserta.');
            }
            $count = $this->db->table('recruitment_schedules')->where('session_id', $id)->where('status !=', 'cancelled')->countAllResults();
            if ($session['capacity'] !== null && $count + count($ids) > (int) $session['capacity']) {
                throw new InvalidArgumentException('Jumlah peserta yang dipilih melebihi sisa kuota agenda.');
            }
            // Existing individual schedules are moved into this session, retaining their history.
            $existing = $this->db->table('recruitment_schedules')->whereIn('application_id', $ids)
                ->whereIn('status', RecruitmentScheduleService::ACTIVE_STATUSES)->get()->getResultArray();
            $byApplication = [];
            foreach ($existing as $row) {
                if ($row['session_id'] !== null || (int) $row['stage_id'] !== (int) $session['stage_id'] || isset($byApplication[$row['application_id']])) {
                    throw new InvalidArgumentException('Ada peserta yang memiliki agenda aktif lain. Batalkan atau selesaikan jadwal tersebut terlebih dahulu.');
                }
                $byApplication[$row['application_id']] = $row;
            }
            $this->checkPicConflict($session, $id, array_column($existing, 'id'));
            foreach ($eligible as $application) {
                $this->checkApplicantConflict((int) $application['id'], $session['starts_at'], $id, $session['ends_at']);
                $data = ['session_id' => $id, 'scheduled_at' => $session['starts_at'], 'venue' => $session['venue'],
                    'pic_user_id' => (int) $session['pic_user_id'], 'instructions' => $session['instructions'], 'confirmation_deadline_at' => $deadline];
                if (isset($byApplication[$application['id']])) {
                    $this->schedules->update((int) $byApplication[$application['id']]['id'], $data, $userId);
                } else {
                    $this->schedules->create((int) $application['id'], (int) $session['stage_id'], $data, $userId);
                }
            }

            return count($ids);
        });
    }

    public function changeStatus(int $id, string $status, int $userId): void
    {
        $this->requirePermission($userId, 'schedules.manage');
        $this->transaction(function () use ($id, $status, $userId): void {
            $this->lock('recruitment_sessions', $id);
            $session = $this->find($id, $userId);
            if (! in_array($status, ['completed', 'cancelled'], true) || ! in_array($session['status'], ['draft', 'scheduled'], true)) {
                throw new InvalidArgumentException('Perubahan status agenda tidak valid.');
            }
            $active = $this->db->table('recruitment_schedules')->where('session_id', $id)
                ->whereIn('status', RecruitmentScheduleService::ACTIVE_STATUSES)->get()->getResultArray();
            if ($status === 'completed' && ($session['status'] !== 'scheduled' || $session['starts_at'] > date('Y-m-d H:i:s') || $active !== [])) {
                throw new InvalidArgumentException('Agenda dapat diselesaikan setelah dimulai dan seluruh kehadiran peserta sudah dicatat atau dibatalkan.');
            }
            if ($status === 'cancelled') {
                foreach ($active as $row) {
                    $this->schedules->setStatus((int) $row['id'], 'cancelled', $userId, 'Agenda seleksi dibatalkan.');
                }
            }
            $this->db->table('recruitment_sessions')->where('id', $id)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        });
    }

    public function participantStatus(int $sessionId, int $scheduleId, string $status, int $userId): void
    {
        $this->requirePermission($userId, $status === 'cancelled' ? 'schedules.manage' : 'schedules.attendance');
        $this->transaction(function () use ($sessionId, $scheduleId, $status, $userId): void {
            $this->lock('recruitment_sessions', $sessionId);
            $session = $this->find($sessionId, $userId);
            $row = $this->participants($sessionId)->where('schedules.id', $scheduleId)->get()->getRowArray();
            if ($session['status'] !== 'scheduled' || $row === null || $row['status'] === 'cancelled') {
                throw new InvalidArgumentException('Peserta atau agenda sudah tidak aktif.');
            }
            if (! in_array($status, ['present', 'absent', 'cancelled'], true)) {
                throw new InvalidArgumentException('Status peserta tidak valid.');
            }
            if ($status === 'cancelled' && ! in_array($row['status'], RecruitmentScheduleService::ACTIVE_STATUSES, true)) {
                throw new InvalidArgumentException('Peserta yang sudah dicatat kehadirannya tidak dapat dibatalkan.');
            }
            if ($status !== 'cancelled' && $row['scheduled_at'] > date('Y-m-d H:i:s')) {
                throw new InvalidArgumentException('Kehadiran baru dapat dicatat setelah jadwal dimulai.');
            }
            $this->schedules->setStatus($scheduleId, $status, $userId);
        });
    }

    public static function parseDate(string $value): string
    {
        foreach (['Y-m-d\TH:i', 'Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, trim($value));
            if ($date !== false && $date->format($format) === trim($value)) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        throw new InvalidArgumentException('Tanggal dan jam tidak valid.');
    }

    private function deadline(string $value, string $start): string
    {
        $value = self::parseDate($value);
        if ($value <= date('Y-m-d H:i:s') || $value >= $start) {
            throw new InvalidArgumentException('Batas konfirmasi harus di masa mendatang dan sebelum agenda dimulai.');
        }

        return $value;
    }

    private function checkPicConflict(array $session, ?int $id, array $ignoreSchedules = [], bool $checkIndividualSchedules = true): void
    {
        if ($session['status'] !== 'scheduled') {
            return;
        }
        $start = $session['starts_at'];
        $end = $session['ends_at'] ?? $start;
        $query = $this->db->table('recruitment_sessions')->where('pic_user_id', $session['pic_user_id'])->where('status', 'scheduled')
            ->where($end === $start ? 'starts_at <=' : 'starts_at <', $end)->groupStart()->where('ends_at >', $start)
            ->orGroupStart()->where('ends_at', null)->where('starts_at >=', $start)->groupEnd()->groupEnd();
        if ($id !== null) {
            $query->where('id !=', $id);
        }
        if ($query->countAllResults() > 0) {
            throw new InvalidArgumentException('PIC memiliki agenda lain yang bertabrakan dengan waktu ini.');
        }
        // Empty sessions may group existing individual schedules at the same time.
        // Participant attachment validates those schedules before moving them.
        if (! $checkIndividualSchedules) {
            return;
        }
        $query = $this->db->table('recruitment_schedules')->where('pic_user_id', $session['pic_user_id'])
            ->whereIn('status', RecruitmentScheduleService::ACTIVE_STATUSES)->where('scheduled_at >=', $start)->where($end === $start ? 'scheduled_at <=' : 'scheduled_at <', $end);
        if ($id !== null) {
            $query->groupStart()->where('session_id', null)->orWhere('session_id !=', $id)->groupEnd();
        }
        if ($ignoreSchedules !== []) {
            $query->whereNotIn('id', $ignoreSchedules);
        }
        if ($query->countAllResults() > 0) {
            throw new InvalidArgumentException('PIC memiliki jadwal peserta lain pada waktu agenda ini.');
        }
    }

    private function checkApplicantConflict(int $applicationId, string $start, int $sessionId, ?string $end = null): void
    {
        $applicantId = $this->db->table('applications')->select('applicant_id')->where('id', $applicationId)->get()->getRowArray()['applicant_id'] ?? 0;
        $this->lock('applicants', (int) $applicantId);
        $end ??= $start;
        $count = $this->db->table('recruitment_schedules AS schedules')->join('applications', 'applications.id = schedules.application_id')
            ->join('recruitment_sessions AS other_session', 'other_session.id = schedules.session_id', 'left')
            ->where('applications.applicant_id', $applicantId)->where('applications.id !=', $applicationId)
            ->whereIn('schedules.status', RecruitmentScheduleService::ACTIVE_STATUSES)
            ->groupStart()
                ->groupStart()->where('schedules.scheduled_at >=', $start)->where($end === $start ? 'schedules.scheduled_at <=' : 'schedules.scheduled_at <', $end)->groupEnd()
                ->orGroupStart()->where('other_session.starts_at <=', $start)->where('other_session.ends_at >', $start)->groupEnd()
            ->groupEnd()->countAllResults();
        if ($count > 0) {
            throw new InvalidArgumentException('Ada pelamar yang memiliki jadwal seleksi lain pada jam yang sama.');
        }
        $sameSession = $this->db->table('recruitment_schedules AS schedules')->join('applications', 'applications.id = schedules.application_id')
            ->where('applications.applicant_id', $applicantId)->where('applications.id !=', $applicationId)
            ->where('schedules.session_id', $sessionId)->where('schedules.status !=', 'cancelled')->countAllResults();
        if ($sameSession > 0) {
            throw new InvalidArgumentException('Pelamar yang sama tidak dapat mengikuti satu agenda melalui dua lowongan.');
        }
    }

    private function requireEditable(array $session, bool $allowStarted = false): void
    {
        if (! in_array($session['status'], ['draft', 'scheduled'], true)
            || (! $allowStarted && $session['starts_at'] <= date('Y-m-d H:i:s'))) {
            throw new InvalidArgumentException('Agenda yang sudah dimulai, selesai, atau dibatalkan tidak dapat diubah atau ditambah peserta.');
        }
    }

    private function requirePermission(int $userId, string $permission): void
    {
        if (! $this->can($userId, $permission)) {
            throw new InvalidArgumentException('Anda tidak memiliki hak akses untuk tindakan ini.');
        }
    }

    private function teamIds(int $userId): array
    {
        return array_column($this->db->table('hrd_team_users')->where('user_id', $userId)->get()->getResultArray(), 'hrd_team_id') ?: [0];
    }

    private function picIds(int $userId): array
    {
        return array_unique(array_merge([$userId], array_map('intval', array_column($this->db->table('hrd_team_users')->whereIn('hrd_team_id', $this->teamIds($userId))->get()->getResultArray(), 'user_id'))));
    }

    private function lock(string $table, int $id): void
    {
        // SQLite serializes writes at database level; MySQL uses row locks.
        $suffix = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
        $this->db->query('SELECT id FROM ' . $this->db->protectIdentifiers($table, true) . ' WHERE id = ?' . $suffix, [$id]);
    }

    private function transaction(callable $callback): mixed
    {
        $this->db->transBegin();
        try {
            $result = $callback();
            if (! $this->db->transStatus()) {
                throw new InvalidArgumentException('Perubahan gagal disimpan. Silakan muat ulang dan coba kembali.');
            }
            $this->db->transCommit();

            return $result;
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }
}
