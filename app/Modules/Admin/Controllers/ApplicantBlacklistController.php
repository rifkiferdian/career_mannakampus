<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Recruitment\Services\ApplicantBlacklistService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;
use DateTimeImmutable;

class ApplicantBlacklistController extends BaseController
{
    private const PER_PAGE = 50;

    private const DURATION_LABELS = [
        '1_month' => '1 bulan',
        '3_months' => '3 bulan',
        '6_months' => '6 bulan',
        '1_year' => '1 tahun',
        '2_years' => '2 tahun',
        'custom' => 'Tanggal khusus',
        'permanent' => 'Permanen',
    ];

    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100);
        $status = trim((string) $this->request->getGet('status'));
        if (! in_array($status, ['', 'active', 'permanent', 'expired', 'revoked'], true)) {
            $status = '';
        }

        $builder = $database->table('applicant_blacklists AS blacklists')
            ->select('blacklists.*, applicants.full_name, applicants.email, applicants.phone, creator.full_name AS created_by_name, updater.full_name AS updated_by_name, revoker.full_name AS revoked_by_name')
            ->join('applicants', 'applicants.id = blacklists.applicant_id')
            ->join('users AS creator', 'creator.id = blacklists.created_by', 'left')
            ->join('users AS updater', 'updater.id = blacklists.updated_by', 'left')
            ->join('users AS revoker', 'revoker.id = blacklists.revoked_by', 'left')
            ->where('applicants.deleted_at', null);
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('applicants.full_name', $keyword)
                ->orLike('applicants.email', $keyword)
                ->orLike('applicants.phone', $keyword)
                ->orLike('blacklists.reason', $keyword)
                ->groupEnd();
        }
        $allRows = $builder->orderBy('blacklists.updated_at', 'DESC')->get()->getResultArray();
        $now = new DateTimeImmutable();
        foreach ($allRows as &$row) {
            $row['computed_status'] = $this->status($row, $now);
        }
        unset($row);

        $vacanciesByApplicant = [];
        $applicantIds = array_values(array_unique(array_map('intval', array_column($allRows, 'applicant_id'))));
        if ($applicantIds !== []) {
            foreach ($database->table('applications AS applications')
                ->select('applications.applicant_id, applications.vacancy_id, applications.application_number, applications.submitted_at, vacancies.title AS vacancy_title')
                ->join('vacancies', 'vacancies.id = applications.vacancy_id')
                ->whereIn('applications.applicant_id', $applicantIds)
                ->where('applications.deleted_at', null)
                ->orderBy('applications.submitted_at', 'DESC')
                ->orderBy('applications.id', 'DESC')
                ->get()->getResultArray() as $application) {
                $applicantId = (int) $application['applicant_id'];
                $vacancyId = (int) $application['vacancy_id'];
                $vacanciesByApplicant[$applicantId][$vacancyId] ??= $application;
            }
        }
        foreach ($allRows as &$row) {
            $row['applied_vacancies'] = array_values($vacanciesByApplicant[(int) $row['applicant_id']] ?? []);
        }
        unset($row);

        $rows = $status === '' ? $allRows : array_values(array_filter(
            $allRows,
            static fn (array $row): bool => $row['computed_status'] === $status
        ));
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) $this->request->getGet('page')), $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = array_slice($rows, $offset, self::PER_PAGE);

        $historiesByBlacklist = [];
        $blacklistIds = array_map('intval', array_column($rows, 'id'));
        if ($blacklistIds !== []) {
            foreach ($database->table('applicant_blacklist_histories AS histories')
                ->select('histories.*, users.full_name AS changed_by_name')
                ->join('users', 'users.id = histories.changed_by', 'left')
                ->whereIn('histories.blacklist_id', $blacklistIds)
                ->orderBy('histories.created_at', 'DESC')
                ->orderBy('histories.id', 'DESC')
                ->get()->getResultArray() as $history) {
                $historiesByBlacklist[(int) $history['blacklist_id']][] = $history;
            }
        }

        return view('admin/applicant_blacklist', [
            'auth' => $auth,
            'blacklists' => $rows,
            'pagination' => ['page' => $page, 'per_page' => self::PER_PAGE, 'total' => $total, 'total_pages' => $totalPages, 'offset' => $offset],
            'historiesByBlacklist' => $historiesByBlacklist,
            'filters' => ['keyword' => $keyword, 'status' => $status],
            'durationLabels' => self::DURATION_LABELS,
            'canManage' => Services::authorization()->can($userId, 'applicants.blacklist.manage'),
            'canViewCandidate' => Services::authorization()->can($userId, 'candidates.view'),
            'summary' => [
                'total' => count($allRows),
                'active' => count(array_filter($allRows, static fn (array $row): bool => $row['computed_status'] === 'active')),
                'permanent' => count(array_filter($allRows, static fn (array $row): bool => $row['computed_status'] === 'permanent')),
                'ended' => count(array_filter($allRows, static fn (array $row): bool => in_array($row['computed_status'], ['expired', 'revoked'], true))),
            ],
            'success' => session()->getFlashdata('blacklist_success'),
            'error' => session()->getFlashdata('blacklist_error'),
        ]);
    }

    public function create(int $applicantId): RedirectResponse
    {
        $database = db_connect();
        $userId = $this->currentUserId();
        $applicant = $database->table('applicants')
            ->select('id, full_name')
            ->where('id', $applicantId)
            ->where('deleted_at', null)
            ->get()->getRowArray();
        if ($applicant === null) {
            return $this->failure('Pelamar tidak ditemukan.', $applicantId);
        }

        $data = $this->validatedData();
        if (is_string($data)) {
            return $this->failure($data, $applicantId);
        }

        $existing = $database->table('applicant_blacklists')->where('applicant_id', $applicantId)->get()->getRowArray();
        if ($existing !== null && in_array($this->status($existing, new DateTimeImmutable()), ['active', 'permanent'], true)) {
            return $this->failure('Pelamar masih berada dalam blacklist aktif.', $applicantId);
        }

        $now = date('Y-m-d H:i:s');
        $database->transStart();
        if ($existing === null) {
            $database->table('applicant_blacklists')->insert($data + [
                'applicant_id' => $applicantId,
                'revoked_at' => null,
                'revoked_by' => null,
                'revocation_reason' => null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $blacklistId = (int) $database->insertID();
            $action = 'blacklisted';
        } else {
            $blacklistId = (int) $existing['id'];
            $database->table('applicant_blacklists')->where('id', $blacklistId)->update($data + [
                'revoked_at' => null,
                'revoked_by' => null,
                'revocation_reason' => null,
                'updated_by' => $userId,
                'updated_at' => $now,
            ]);
            $action = 'reactivated';
        }
        $this->insertHistory($blacklistId, $action, $data, 'Blacklist diaktifkan untuk seluruh pendaftaran lowongan.', $userId, $now);
        $database->transComplete();

        if (! $database->transStatus()) {
            return $this->failure('Blacklist gagal disimpan.', $applicantId);
        }

        return $this->success($applicant['full_name'] . ' berhasil dimasukkan ke blacklist.', $applicantId);
    }

    public function update(int $blacklistId): RedirectResponse
    {
        $database = db_connect();
        $blacklist = $database->table('applicant_blacklists AS blacklists')
            ->select('blacklists.*, applicants.full_name')
            ->join('applicants', 'applicants.id = blacklists.applicant_id')
            ->where('blacklists.id', $blacklistId)
            ->where('applicants.deleted_at', null)
            ->get()->getRowArray();
        if ($blacklist === null) {
            return $this->failure('Data blacklist tidak ditemukan.');
        }
        $data = $this->validatedData();
        if (is_string($data)) {
            return $this->failure($data, (int) $blacklist['applicant_id']);
        }

        $userId = $this->currentUserId();
        $now = date('Y-m-d H:i:s');
        $action = in_array($this->status($blacklist, new DateTimeImmutable()), ['active', 'permanent'], true)
            ? 'updated'
            : 'reactivated';
        $database->transStart();
        $database->table('applicant_blacklists')->where('id', $blacklistId)->update($data + [
            'revoked_at' => null,
            'revoked_by' => null,
            'revocation_reason' => null,
            'updated_by' => $userId,
            'updated_at' => $now,
        ]);
        $this->insertHistory(
            $blacklistId,
            $action,
            $data,
            $action === 'reactivated' ? 'Blacklist diaktifkan kembali.' : 'Ketentuan blacklist diperbarui.',
            $userId,
            $now
        );
        $database->transComplete();

        if (! $database->transStatus()) {
            return $this->failure('Perubahan blacklist gagal disimpan.', (int) $blacklist['applicant_id']);
        }

        return $this->success('Blacklist ' . $blacklist['full_name'] . ' berhasil diperbarui.', (int) $blacklist['applicant_id']);
    }

    public function revoke(int $blacklistId): RedirectResponse
    {
        $database = db_connect();
        $blacklist = $database->table('applicant_blacklists AS blacklists')
            ->select('blacklists.*, applicants.full_name')
            ->join('applicants', 'applicants.id = blacklists.applicant_id')
            ->where('blacklists.id', $blacklistId)
            ->where('applicants.deleted_at', null)
            ->get()->getRowArray();
        if ($blacklist === null) {
            return $this->failure('Data blacklist tidak ditemukan.');
        }
        if (! in_array($this->status($blacklist, new DateTimeImmutable()), ['active', 'permanent'], true)) {
            return $this->failure('Blacklist sudah berakhir atau telah dicabut.', (int) $blacklist['applicant_id']);
        }
        $reason = mb_substr(trim((string) $this->request->getPost('revocation_reason')), 0, 1000);
        if (mb_strlen($reason) < 5) {
            return $this->failure('Alasan pencabutan minimal 5 karakter.', (int) $blacklist['applicant_id']);
        }

        $userId = $this->currentUserId();
        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('applicant_blacklists')->where('id', $blacklistId)->where('revoked_at', null)->update([
            'revoked_at' => $now,
            'revoked_by' => $userId,
            'revocation_reason' => $reason,
            'updated_by' => $userId,
            'updated_at' => $now,
        ]);
        $revoked = $database->affectedRows() === 1;
        if ($revoked) {
            $this->insertHistory($blacklistId, 'revoked', $blacklist, $reason, $userId, $now);
        }
        $database->transComplete();

        if (! $revoked || ! $database->transStatus()) {
            return $this->failure('Blacklist gagal dicabut. Silakan muat ulang halaman.', (int) $blacklist['applicant_id']);
        }

        return $this->success('Blacklist ' . $blacklist['full_name'] . ' berhasil dicabut.', (int) $blacklist['applicant_id']);
    }

    /** @return array<string, mixed>|string */
    private function validatedData(): array|string
    {
        $reason = mb_substr(trim((string) $this->request->getPost('reason')), 0, 1000);
        $notes = mb_substr(trim((string) $this->request->getPost('internal_notes')), 0, 5000);
        $duration = trim((string) $this->request->getPost('duration'));
        if (mb_strlen($reason) < 5) {
            return 'Alasan blacklist minimal 5 karakter.';
        }
        if (! isset(self::DURATION_LABELS[$duration])) {
            return 'Masa berlaku blacklist tidak valid.';
        }

        $start = new DateTimeImmutable();
        $isPermanent = $duration === 'permanent';
        $end = null;
        if (! $isPermanent) {
            if ($duration === 'custom') {
                $dateValue = trim((string) $this->request->getPost('ends_on'));
                $end = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue) ?: null;
                if ($end === null || $end->format('Y-m-d') !== $dateValue) {
                    return 'Tanggal berakhir blacklist tidak valid.';
                }
                $end = $end->setTime(23, 59, 59);
            } else {
                $modifier = match ($duration) {
                    '1_month' => '+1 month',
                    '3_months' => '+3 months',
                    '6_months' => '+6 months',
                    '1_year' => '+1 year',
                    '2_years' => '+2 years',
                };
                $end = $start->modify($modifier)->setTime(23, 59, 59);
            }
            if ($end <= $start) {
                return 'Tanggal berakhir harus setelah waktu mulai blacklist.';
            }
        }

        return [
            'reason' => $reason,
            'internal_notes' => $notes !== '' ? $notes : null,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $end?->format('Y-m-d H:i:s'),
            'is_permanent' => $isPermanent ? 1 : 0,
        ];
    }

    /** @param array<string, mixed> $snapshot */
    private function insertHistory(int $blacklistId, string $action, array $snapshot, string $actionNotes, int $userId, string $now): void
    {
        db_connect()->table('applicant_blacklist_histories')->insert([
            'blacklist_id' => $blacklistId,
            'action' => $action,
            'reason_snapshot' => $snapshot['reason'] ?? null,
            'notes_snapshot' => $snapshot['internal_notes'] ?? null,
            'starts_at_snapshot' => $snapshot['starts_at'] ?? null,
            'ends_at_snapshot' => $snapshot['ends_at'] ?? null,
            'is_permanent_snapshot' => (int) ($snapshot['is_permanent'] ?? 0),
            'action_notes' => $actionNotes,
            'changed_by' => $userId,
            'created_at' => $now,
        ]);
    }

    /** @param array<string, mixed> $blacklist */
    private function status(array $blacklist, DateTimeImmutable $now): string
    {
        return ApplicantBlacklistService::statusOf($blacklist, $now);
    }

    private function currentUserId(): int
    {
        return (int) (session()->get('hrd_auth')['user_id'] ?? 0);
    }

    private function success(string $message, int $applicantId): RedirectResponse
    {
        return redirect()->to($this->returnUrl($applicantId))->with('blacklist_success', $message);
    }

    private function failure(string $message, int $applicantId = 0): RedirectResponse
    {
        return redirect()->to($this->returnUrl($applicantId))->withInput()->with('blacklist_error', $message);
    }

    private function returnUrl(int $applicantId): string
    {
        return $this->request->getPost('return_to') === 'detail' && $applicantId > 0
            ? site_url('adminhrdmannakampus/pelamar/' . $applicantId)
            : site_url('adminhrdmannakampus/blacklist-pelamar');
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
