<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;
use DateTimeImmutable;

class VacancyPeriodController extends BaseController
{
    private const PER_PAGE = 50;
    private const STATUSES = ['draft', 'scheduled', 'open', 'closed', 'archived'];

    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $vacancyId = max(0, (int) $this->request->getGet('vacancy_id'));
        $status = trim((string) $this->request->getGet('status'));
        if (! in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $summaryBuilder = $database->table('vacancy_recruitment_periods AS periods')
            ->select("COUNT(DISTINCT periods.id) AS total, COUNT(DISTINCT CASE WHEN periods.status = 'open' THEN periods.id END) AS open_total, COUNT(DISTINCT CASE WHEN periods.status = 'scheduled' THEN periods.id END) AS scheduled_total, COUNT(DISTINCT applications.id) AS application_total", false)
            ->join('vacancies', 'vacancies.id = periods.vacancy_id')
            ->join('applications', 'applications.vacancy_period_id = periods.id AND applications.deleted_at IS NULL', 'left')
            ->where('periods.deleted_at', null)
            ->where('vacancies.deleted_at', null);
        $this->applyIndexFilters($summaryBuilder, $vacancyId, $status);
        $summary = $summaryBuilder->get()->getRowArray() ?? [];
        $total = (int) ($summary['total'] ?? 0);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) $this->request->getGet('page')), $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;

        $builder = $database->table('vacancy_recruitment_periods AS periods')
            ->select('periods.*, vacancies.title AS vacancy_title, vacancies.code AS vacancy_code, departments.name AS department_name, COUNT(applications.id) AS application_count')
            ->join('vacancies', 'vacancies.id = periods.vacancy_id')
            ->join('departments', 'departments.id = vacancies.department_id', 'left')
            ->join('applications', 'applications.vacancy_period_id = periods.id AND applications.deleted_at IS NULL', 'left')
            ->where('periods.deleted_at', null)
            ->where('vacancies.deleted_at', null)
            ->groupBy('periods.id')
            ->orderBy('periods.opened_at', 'DESC')
            ->orderBy('periods.id', 'DESC');
        $this->applyIndexFilters($builder, $vacancyId, $status);

        return view('admin/vacancy_periods', [
            'auth' => $auth,
            'periods' => $builder->limit(self::PER_PAGE, $offset)->get()->getResultArray(),
            'summary' => [
                'total' => $total,
                'open' => (int) ($summary['open_total'] ?? 0),
                'scheduled' => (int) ($summary['scheduled_total'] ?? 0),
                'applications' => (int) ($summary['application_total'] ?? 0),
            ],
            'pagination' => ['page' => $page, 'per_page' => self::PER_PAGE, 'total' => $total, 'total_pages' => $totalPages, 'offset' => $offset],
            'vacancies' => $database->table('vacancies')->select('id, title, code')->where('deleted_at', null)->orderBy('title')->get()->getResultArray(),
            'selectedVacancyId' => $vacancyId,
            'selectedStatus' => $status,
            'statusLabels' => $this->statusLabels(),
            'canManage' => Services::authorization()->can($userId, 'vacancy.periods.manage'),
            'canPublish' => Services::authorization()->can($userId, 'vacancy.periods.publish'),
            'success' => session()->getFlashdata('period_success'),
            'error' => session()->getFlashdata('period_error'),
            'openModal' => (string) (session()->getFlashdata('period_form') ?? ''),
        ]);
    }

    private function applyIndexFilters(BaseBuilder $builder, int $vacancyId, string $status): void
    {
        if ($vacancyId > 0) {
            $builder->where('periods.vacancy_id', $vacancyId);
        }
        if ($status !== '') {
            $builder->where('periods.status', $status);
        }
    }

    public function create(): RedirectResponse
    {
        $data = $this->validatedInput(null);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        if ($this->hasConflictingPeriod((int) $data['vacancy_id'], null, (string) $data['status'], $data['opened_at'], $data['closed_at'])) {
            return $this->formError('Lowongan tersebut sudah memiliki sesi aktif atau terjadwal pada rentang waktu yang sama.', 'create');
        }

        $now = date('Y-m-d H:i:s');
        $userId = $this->currentUserId();
        try {
            db_connect()->table('vacancy_recruitment_periods')->insert($data + [
                'is_initial' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (DatabaseException) {
            return $this->formError('Sesi gagal dibuat. Silakan periksa kembali datanya.', 'create');
        }
        $this->syncVacancySummary((int) $data['vacancy_id']);

        return $this->success('Sesi lowongan berhasil dibuat.');
    }

    public function update(int $periodId): RedirectResponse
    {
        $period = $this->findPeriod($periodId);
        if ($period === null) {
            return $this->error('Sesi lowongan tidak ditemukan.');
        }
        $data = $this->validatedInput($period);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        if ($this->hasConflictingPeriod((int) $data['vacancy_id'], $periodId, (string) $data['status'], $data['opened_at'], $data['closed_at'])) {
            return $this->formError('Lowongan tersebut sudah memiliki sesi aktif atau terjadwal pada rentang waktu yang sama.', 'edit-' . $periodId);
        }

        try {
            db_connect()->table('vacancy_recruitment_periods')->where('id', $periodId)->update($data + [
                'updated_by' => $this->currentUserId(),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (DatabaseException) {
            return $this->formError('Sesi gagal diperbarui. Silakan periksa kembali datanya.', 'edit-' . $periodId);
        }
        if ((int) $period['vacancy_id'] !== (int) $data['vacancy_id']) {
            $this->syncVacancySummary((int) $period['vacancy_id']);
        }
        $this->syncVacancySummary((int) $data['vacancy_id']);

        return $this->success('Sesi lowongan berhasil diperbarui.');
    }

    public function changeStatus(int $periodId): RedirectResponse
    {
        $period = $this->findPeriod($periodId);
        $status = trim((string) $this->request->getPost('status'));
        if ($period === null || ! in_array($status, self::STATUSES, true)) {
            return $this->error('Sesi atau status tidak valid.');
        }
        $proposedOpenedAt = $period['opened_at'];
        $proposedClosedAt = $period['closed_at'];
        if ($status === 'open' && empty($proposedOpenedAt)) {
            $proposedOpenedAt = date('Y-m-d H:i:s');
        }
        if ($status === 'open' && $proposedClosedAt && strtotime((string) $proposedClosedAt) <= time()) {
            $proposedClosedAt = null;
        }
        if ($status === 'closed') {
            $proposedClosedAt = date('Y-m-d H:i:s');
        }
        if ($this->hasConflictingPeriod((int) $period['vacancy_id'], $periodId, $status, $proposedOpenedAt, $proposedClosedAt)) {
            return $this->error('Lowongan tersebut sudah memiliki sesi aktif atau terjadwal pada rentang waktu yang sama.');
        }

        $data = ['status' => $status, 'opened_at' => $proposedOpenedAt, 'closed_at' => $proposedClosedAt, 'updated_by' => $this->currentUserId(), 'updated_at' => date('Y-m-d H:i:s')];
        db_connect()->table('vacancy_recruitment_periods')->where('id', $periodId)->update($data);
        $this->syncVacancySummary((int) $period['vacancy_id']);

        return $this->success('Status sesi lowongan berhasil diubah.');
    }

    public function delete(int $periodId): RedirectResponse
    {
        $period = $this->findPeriod($periodId);
        if ($period === null) {
            return $this->error('Sesi lowongan tidak ditemukan.');
        }
        if (db_connect()->table('applications')->where('vacancy_period_id', $periodId)->countAllResults() > 0) {
            return $this->error('Sesi sudah memiliki pelamar dan tidak dapat dihapus. Tutup atau arsipkan sesi tersebut.');
        }

        db_connect()->table('vacancy_recruitment_periods')->where('id', $periodId)->delete();
        $this->syncVacancySummary((int) $period['vacancy_id']);

        return $this->success('Sesi lowongan berhasil dihapus.');
    }

    /** @param array<string, mixed>|null $existing */
    private function validatedInput(?array $existing): array|RedirectResponse
    {
        $vacancyId = max(0, (int) $this->request->getPost('vacancy_id'));
        $name = trim((string) $this->request->getPost('period_name'));
        $openedAt = $this->dateTime((string) $this->request->getPost('opened_at'));
        $closedAt = $this->dateTime((string) $this->request->getPost('closed_at'));
        $headcount = (int) $this->request->getPost('headcount');
        $notes = mb_substr(trim((string) $this->request->getPost('notes')), 0, 2000);
        $status = trim((string) $this->request->getPost('status'));
        $form = $existing === null ? 'create' : 'edit-' . (int) $existing['id'];

        if (db_connect()->table('vacancies')->where('id', $vacancyId)->where('deleted_at', null)->countAllResults() === 0) {
            return $this->formError('Pilih lowongan yang valid.', $form);
        }
        if ($existing !== null && (int) $existing['vacancy_id'] !== $vacancyId && db_connect()->table('applications')->where('vacancy_period_id', $existing['id'])->countAllResults() > 0) {
            return $this->formError('Lowongan pada sesi yang sudah memiliki pelamar tidak dapat diganti.', $form);
        }
        if ($name === '' || mb_strlen($name) > 150) {
            return $this->formError('Nama periode wajib diisi dan maksimal 150 karakter.', $form);
        }
        $code = $existing === null || (int) $existing['vacancy_id'] !== $vacancyId
            ? $this->uniquePeriodCode($vacancyId, $name)
            : (string) $existing['period_code'];
        if ($openedAt === false || $closedAt === false) {
            return $this->formError('Format tanggal mulai atau tanggal berakhir tidak valid.', $form);
        }
        if ($openedAt !== null && $closedAt !== null && $closedAt <= $openedAt) {
            return $this->formError('Tanggal berakhir harus setelah tanggal mulai.', $form);
        }
        if ($headcount < 1 || $headcount > 9999) {
            return $this->formError('Jumlah kebutuhan harus antara 1 sampai 9.999 orang.', $form);
        }

        $canPublish = Services::authorization()->can($this->currentUserId(), 'vacancy.periods.publish');
        if (! $canPublish) {
            $status = $existing === null ? 'draft' : (string) $existing['status'];
        }
        if (! in_array($status, self::STATUSES, true)) {
            return $this->formError('Status sesi tidak valid.', $form);
        }

        return [
            'vacancy_id' => $vacancyId,
            'period_name' => $name,
            'period_code' => $code,
            'opened_at' => $openedAt?->format('Y-m-d H:i:s'),
            'closed_at' => $closedAt?->format('Y-m-d H:i:s'),
            'headcount' => $headcount,
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null,
        ];
    }

    private function dateTime(string $value): DateTimeImmutable|false|null
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);

        return $date !== false && $date->format('Y-m-d\TH:i') === $value ? $date : false;
    }

    private function uniquePeriodCode(int $vacancyId, string $name): string
    {
        $asciiName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $base = mb_strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $asciiName ?: $name), '-'));
        $base = mb_substr($base !== '' ? $base : 'periode', 0, 80);
        $code = $base;
        $suffix = 2;

        while (db_connect()->table('vacancy_recruitment_periods')->where('vacancy_id', $vacancyId)->where('period_code', $code)->countAllResults() > 0) {
            $number = '-' . $suffix++;
            $code = mb_substr($base, 0, 80 - strlen($number)) . $number;
        }

        return $code;
    }

    private function hasConflictingPeriod(int $vacancyId, ?int $exceptId, string $newStatus, mixed $openedAt, mixed $closedAt): bool
    {
        if (! in_array($newStatus, ['open', 'scheduled'], true)) {
            return false;
        }
        $builder = db_connect()->table('vacancy_recruitment_periods')->where('vacancy_id', $vacancyId)->whereIn('status', ['open', 'scheduled'])->where('deleted_at', null);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        $newStart = $openedAt ? (strtotime((string) $openedAt) ?: PHP_INT_MIN) : PHP_INT_MIN;
        $newEnd = $closedAt ? (strtotime((string) $closedAt) ?: PHP_INT_MAX) : PHP_INT_MAX;
        foreach ($builder->get()->getResultArray() as $period) {
            $existingStart = $period['opened_at'] ? (strtotime((string) $period['opened_at']) ?: PHP_INT_MIN) : PHP_INT_MIN;
            $existingEnd = $period['closed_at'] ? (strtotime((string) $period['closed_at']) ?: PHP_INT_MAX) : PHP_INT_MAX;
            if ($newStart <= $existingEnd && $newEnd >= $existingStart) {
                return true;
            }
        }

        return false;
    }

    private function syncVacancySummary(int $vacancyId): void
    {
        $database = db_connect();
        $open = $database->table('vacancy_recruitment_periods')->where('vacancy_id', $vacancyId)->where('status', 'open')->where('deleted_at', null)->orderBy('opened_at', 'DESC')->get()->getRowArray();
        if ($open !== null) {
            $database->table('vacancies')->where('id', $vacancyId)->update([
                'status' => 'open',
                'opened_at' => $open['opened_at'],
                'closed_at' => $open['closed_at'],
                'headcount' => $open['headcount'],
                'updated_by' => $this->currentUserId(),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return;
        }
        $database->table('vacancies')->where('id', $vacancyId)->where('status !=', 'archived')->update([
            'status' => 'closed',
            'updated_by' => $this->currentUserId(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function findPeriod(int $periodId): ?array
    {
        return db_connect()->table('vacancy_recruitment_periods')->where('id', $periodId)->where('deleted_at', null)->get()->getRowArray() ?: null;
    }

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return ['draft' => 'Draft', 'scheduled' => 'Terjadwal', 'open' => 'Dibuka', 'closed' => 'Ditutup', 'archived' => 'Diarsipkan'];
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->to($this->indexUrl())->with('period_success', $message);
    }

    private function error(string $message): RedirectResponse
    {
        return redirect()->to($this->indexUrl())->with('period_error', $message);
    }

    private function formError(string $message, string $form): RedirectResponse
    {
        return redirect()->to($this->indexUrl())->withInput()->with('period_error', $message)->with('period_form', $form);
    }

    private function indexUrl(): string
    {
        $vacancyId = max(0, (int) ($this->request->getPost('vacancy_id') ?: $this->request->getGet('vacancy_id')));

        return site_url('adminhrdmannakampus/sesi-lowongan') . ($vacancyId > 0 ? '?vacancy_id=' . $vacancyId : '');
    }

    private function currentUserId(): int
    {
        $auth = session()->get('hrd_auth');

        return is_array($auth) ? (int) ($auth['user_id'] ?? 0) : 0;
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
