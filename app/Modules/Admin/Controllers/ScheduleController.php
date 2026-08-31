<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class ScheduleController extends BaseController
{
    public function update(int $scheduleId): RedirectResponse
    {
        $schedule = $this->authorizedSchedule($scheduleId);
        if ($schedule === null) {
            return $this->error('Jadwal tidak ditemukan atau tidak dapat Anda kelola.');
        }
        if (in_array((string) $schedule['status'], ['present', 'absent', 'cancelled'], true)) {
            return $this->error('Jadwal yang sudah selesai atau dibatalkan tidak dapat diubah.');
        }

        try {
            $data = Services::recruitmentSchedule()->validateInput([
                'scheduled_at' => $this->request->getPost('scheduled_at'),
                'venue' => $this->request->getPost('venue'),
                'pic_user_id' => $this->request->getPost('pic_user_id'),
                'instructions' => $this->request->getPost('instructions'),
                'confirmation_deadline_at' => $this->request->getPost('confirmation_deadline_at'),
            ], $scheduleId);
            Services::recruitmentSchedule()->update($scheduleId, $data, $this->userId());
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage());
        }

        return $this->success('Jadwal berhasil diperbarui dan menunggu konfirmasi ulang kandidat.');
    }

    public function cancel(int $scheduleId): RedirectResponse
    {
        $schedule = $this->authorizedSchedule($scheduleId);
        if ($schedule === null) {
            return $this->error('Jadwal tidak ditemukan atau tidak dapat Anda kelola.');
        }
        if (! in_array((string) $schedule['status'], ['scheduled', 'confirmed', 'reschedule_requested'], true)) {
            return $this->error('Jadwal ini sudah selesai atau dibatalkan.');
        }
        Services::recruitmentSchedule()->setStatus($scheduleId, 'cancelled', $this->userId(), trim((string) $this->request->getPost('notes')) ?: 'Dibatalkan oleh recruiter.');

        return $this->success('Jadwal berhasil dibatalkan.');
    }

    public function rescheduleAfterAbsence(int $scheduleId): RedirectResponse
    {
        $schedule = $this->authorizedSchedule($scheduleId);
        if ($schedule === null) {
            return $this->error('Jadwal tidak ditemukan atau tidak dapat Anda kelola.');
        }
        if ((string) $schedule['status'] !== 'absent') {
            return $this->error('Hanya jadwal berstatus Tidak hadir yang dapat dijadwalkan ulang.');
        }
        if ((string) $schedule['application_status'] !== (string) $schedule['stage_code']) {
            return $this->error('Tahap pelamar sudah berubah sehingga jadwal lama ini tidak dapat dijadwalkan ulang.');
        }
        if (Services::applicantBlacklist()->isActive((int) $schedule['applicant_id'])) {
            return $this->error('Pelamar berada dalam blacklist aktif dan tidak dapat dijadwalkan ulang.');
        }

        try {
            $data = Services::recruitmentSchedule()->validateInput([
                'scheduled_at' => $this->request->getPost('scheduled_at'),
                'venue' => $this->request->getPost('venue'),
                'pic_user_id' => $this->request->getPost('pic_user_id'),
                'instructions' => $this->request->getPost('instructions'),
                'confirmation_deadline_at' => $this->request->getPost('confirmation_deadline_at'),
            ]);
            Services::recruitmentSchedule()->rescheduleAfterAbsence(
                $scheduleId,
                $data,
                $this->userId(),
                (string) $this->request->getPost('reason'),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage());
        }

        return $this->success('Jadwal ulang berhasil dibuat dan menunggu konfirmasi kandidat.');
    }

    public function attendance(int $scheduleId): RedirectResponse
    {
        $schedule = $this->authorizedSchedule($scheduleId);
        $status = trim((string) $this->request->getPost('status'));
        if ($schedule === null || ! in_array($status, ['present', 'absent'], true)) {
            return $this->error('Jadwal atau status kehadiran tidak valid.');
        }
        if ((string) $schedule['status'] === 'cancelled') {
            return $this->error('Kehadiran tidak dapat dicatat pada jadwal yang dibatalkan.');
        }
        if (strtotime((string) $schedule['scheduled_at']) > time()) {
            return $this->error('Kehadiran baru dapat dicatat setelah waktu pelaksanaan dimulai.');
        }
        Services::recruitmentSchedule()->setStatus($scheduleId, $status, $this->userId(), trim((string) $this->request->getPost('notes')));

        return $this->success($status === 'present' ? 'Kandidat dicatat hadir.' : 'Kandidat dicatat tidak hadir.');
    }

    /** @return array<string, mixed>|null */
    private function authorizedSchedule(int $scheduleId): ?array
    {
        $row = db_connect()->table('recruitment_schedules AS schedules')
            ->select('schedules.*, applications.application_status, applicants.id AS applicant_id, applicants.assigned_hrd_team_id, stages.code AS stage_code')
            ->join('applications', 'applications.id = schedules.application_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('recruitment_stages AS stages', 'stages.id = schedules.stage_id')
            ->where('schedules.id', $scheduleId)->where('applications.deleted_at', null)->get()->getRowArray();
        if ($row === null) {
            return null;
        }
        if (Services::authorization()->can($this->userId(), 'schedules.view_all')) {
            return $row;
        }
        $ownsTeam = db_connect()->table('hrd_team_users')->where('user_id', $this->userId())->where('hrd_team_id', (int) $row['assigned_hrd_team_id'])->countAllResults() > 0;

        return $ownsTeam || (int) $row['pic_user_id'] === $this->userId() ? $row : null;
    }

    private function userId(): int
    {
        return (int) (session()->get('hrd_auth')['user_id'] ?? 0);
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->to($this->returnUrl())->with('candidate_success', $message);
    }

    private function error(string $message): RedirectResponse
    {
        return redirect()->to($this->returnUrl())->with('candidate_error', $message);
    }

    private function returnUrl(): string
    {
        if ((string) $this->request->getPost('return_to') === 'attendance-recap') {
            $parameters = [];
            parse_str(mb_substr((string) $this->request->getPost('return_query'), 0, 1500), $parameters);
            $allowed = array_flip(['keyword', 'stage_id', 'vacancy_id', 'team_id', 'status', 'date_from', 'date_to', 'page']);
            $parameters = array_filter(
                array_intersect_key($parameters, $allowed),
                static fn (mixed $value): bool => is_scalar($value) && mb_strlen((string) $value) <= 100,
            );

            return site_url('adminhrdmannakampus/rekap-kehadiran') . ($parameters === [] ? '' : '?' . http_build_query($parameters));
        }
        $teamId = max(0, (int) $this->request->getPost('team_id'));

        return site_url('adminhrdmannakampus/kandidat') . ($teamId > 0 ? '?team_id=' . $teamId : '');
    }
}
