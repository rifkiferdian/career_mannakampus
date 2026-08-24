<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Services\ExcelWorkbookBuilder;
use App\Modules\Admin\Services\ExcelWorkbookReader;
use App\Modules\Recruitment\Services\ApplicantBlacklistService;
use App\Modules\Recruitment\Services\HistoricalBlacklistService;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class HistoricalBlacklistController extends BaseController
{
    private const DURATION_LABELS = [
        '1_month' => '1 bulan',
        '3_months' => '3 bulan',
        '6_months' => '6 bulan',
        '1_year' => '1 tahun',
        '2_years' => '2 tahun',
        'custom' => 'Tanggal khusus',
        'permanent' => 'Permanen',
    ];

    private const TEMPLATE_HEADERS = [
        'Nama Lengkap', 'NIK', 'Email', 'Nomor Telepon', 'Alasan Blacklist',
        'Catatan Internal', 'Sumber Data', 'Masa Berlaku', 'Tanggal Berakhir',
    ];

    public function index(): string
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
        $database = db_connect();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100);
        $status = trim((string) $this->request->getGet('status'));
        if (! in_array($status, ['', 'active', 'permanent', 'expired', 'revoked'], true)) {
            $status = '';
        }

        $builder = $database->table('historical_blacklists AS entries')
            ->select('entries.*, creator.full_name AS created_by_name, updater.full_name AS updated_by_name, revoker.full_name AS revoked_by_name')
            ->join('users AS creator', 'creator.id = entries.created_by', 'left')
            ->join('users AS updater', 'updater.id = entries.updated_by', 'left')
            ->join('users AS revoker', 'revoker.id = entries.revoked_by', 'left');
        if ($keyword !== '') {
            $normalizedPhone = HistoricalBlacklistService::normalizePhone($keyword);
            $builder->groupStart()
                ->like('entries.full_name', $keyword)
                ->orLike('entries.email', $keyword)
                ->orLike('entries.reason', $keyword)
                ->orLike('entries.source', $keyword);
            if ($normalizedPhone !== '') {
                $builder->orLike('entries.phone', $normalizedPhone);
            }
            $builder->groupEnd();
        }
        $allRows = $builder->orderBy('entries.updated_at', 'DESC')->get()->getResultArray();
        $now = new DateTimeImmutable();
        foreach ($allRows as &$row) {
            $row['computed_status'] = ApplicantBlacklistService::statusOf($row, $now);
        }
        unset($row);

        $rows = $status === '' ? $allRows : array_values(array_filter(
            $allRows,
            static fn (array $row): bool => $row['computed_status'] === $status
        ));
        $histories = [];
        $ids = array_map('intval', array_column($rows, 'id'));
        if ($ids !== []) {
            foreach ($database->table('historical_blacklist_histories AS histories')
                ->select('histories.*, users.full_name AS changed_by_name')
                ->join('users', 'users.id = histories.changed_by', 'left')
                ->whereIn('histories.historical_blacklist_id', $ids)
                ->orderBy('histories.created_at', 'DESC')
                ->orderBy('histories.id', 'DESC')->get()->getResultArray() as $history) {
                $histories[(int) $history['historical_blacklist_id']][] = $history;
            }
        }

        return view('admin/historical_blacklist', [
            'auth' => $auth,
            'entries' => $rows,
            'historiesByEntry' => $histories,
            'filters' => ['keyword' => $keyword, 'status' => $status],
            'durationLabels' => self::DURATION_LABELS,
            'canManage' => Services::authorization()->can($userId, 'applicants.blacklist.manage'),
            'summary' => [
                'total' => count($allRows),
                'active' => count(array_filter($allRows, static fn (array $row): bool => $row['computed_status'] === 'active')),
                'permanent' => count(array_filter($allRows, static fn (array $row): bool => $row['computed_status'] === 'permanent')),
                'ended' => count(array_filter($allRows, static fn (array $row): bool => in_array($row['computed_status'], ['expired', 'revoked'], true))),
            ],
            'success' => session()->getFlashdata('historical_blacklist_success'),
            'error' => session()->getFlashdata('historical_blacklist_error'),
        ]);
    }

    public function create(): RedirectResponse
    {
        $data = $this->validatedData();
        if (is_string($data)) {
            return $this->failure($data);
        }
        if ($this->hasDuplicateIdentity($data)) {
            return $this->failure('Identitas tersebut sudah tercatat. Ubah atau aktifkan kembali data yang sudah ada.');
        }

        $database = db_connect();
        $now = date('Y-m-d H:i:s');
        $userId = $this->currentUserId();
        $database->transStart();
        $database->table('historical_blacklists')->insert($data + [
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $database->insertID();
        $this->insertHistory($id, 'blacklisted', 'Data blacklist historis ditambahkan.', $userId, $now);
        $database->transComplete();

        return $database->transStatus()
            ? $this->success($data['full_name'] . ' berhasil dimasukkan ke blacklist historis.')
            : $this->failure('Data blacklist historis gagal disimpan.');
    }

    public function template(): DownloadResponse
    {
        $workbook = (new ExcelWorkbookBuilder())->build(
            'Template Import Blacklist Historis',
            self::TEMPLATE_HEADERS,
            [],
            'Isi mulai baris 5. Masa Berlaku: permanen, 1 bulan, 3 bulan, 6 bulan, 1 tahun, 2 tahun, atau tanggal khusus (tanggal: YYYY-MM-DD).',
            [2, 4],
        );

        return $this->response->download('template-blacklist-historis.xlsx', $workbook, true);
    }

    public function import(): RedirectResponse
    {
        $file = $this->request->getFile('import_file');
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return $this->failure('Pilih file Excel .xlsx yang valid.');
        }
        if ($file->getSize() <= 0 || $file->getSize() > 5 * 1024 * 1024) {
            return $this->failure('Ukuran file Excel maksimal 5 MB.');
        }
        if (mb_strtolower($file->getClientExtension()) !== 'xlsx') {
            return $this->failure('Format file harus .xlsx. Unduh dan gunakan template yang disediakan.');
        }

        try {
            $workbook = (new ExcelWorkbookReader())->read($file->getTempName(), 4, 1000);
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage());
        }
        if (array_slice($workbook['headers'], 0, count(self::TEMPLATE_HEADERS)) !== self::TEMPLATE_HEADERS) {
            return $this->failure('Susunan kolom tidak sesuai template. Jangan mengubah nama atau urutan header.');
        }
        if ($workbook['rows'] === []) {
            return $this->failure('File Excel belum berisi data. Isi data mulai dari baris 5.');
        }

        $database = db_connect();
        $userId = $this->currentUserId();
        $now = date('Y-m-d H:i:s');
        $imported = 0;
        $errors = [];
        $database->transBegin();
        try {
            foreach ($workbook['rows'] as $row) {
                $values = array_pad($row['values'], count(self::TEMPLATE_HEADERS), '');
                $duration = $this->importDuration((string) $values[7]);
                $endsOn = $this->importDate((string) $values[8]);
                $data = $this->validateValues([
                    'full_name' => $values[0],
                    'nik' => $values[1],
                    'email' => $values[2],
                    'phone' => $values[3],
                    'reason' => $values[4],
                    'internal_notes' => $values[5],
                    'source' => $values[6],
                    'duration' => $duration,
                    'ends_on' => $endsOn,
                ]);
                if (is_string($data)) {
                    $errors[] = 'Baris ' . $row['row_number'] . ': ' . $data;
                    continue;
                }
                if ($this->hasDuplicateIdentity($data)) {
                    $errors[] = 'Baris ' . $row['row_number'] . ': NIK, email, atau telepon sudah tercatat.';
                    continue;
                }

                $database->table('historical_blacklists')->insert($data + [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $id = (int) $database->insertID();
                $this->insertHistory($id, 'blacklisted', 'Ditambahkan melalui import Excel.', $userId, $now);
                $imported++;
            }
            if ($database->transStatus() === false) {
                throw new RuntimeException('Penyimpanan hasil import gagal.');
            }
            $database->transCommit();
        } catch (Throwable $exception) {
            $database->transRollback();
            log_message('error', '[Historical Blacklist] Import Excel gagal: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);

            return $this->failure('Import Excel gagal disimpan. Silakan periksa file dan coba kembali.');
        }

        $redirect = redirect()->to(site_url('adminhrdmannakampus/blacklist-historis'));
        if ($imported > 0) {
            $redirect->with('historical_blacklist_success', $imported . ' data berhasil diimport dari Excel.');
        }
        if ($errors !== []) {
            $shownErrors = array_slice($errors, 0, 8);
            $suffix = count($errors) > count($shownErrors) ? ' dan ' . (count($errors) - count($shownErrors)) . ' kesalahan lainnya.' : '';
            $redirect->with('historical_blacklist_error', count($errors) . ' baris tidak diimport. ' . implode(' | ', $shownErrors) . $suffix);
        }

        return $redirect;
    }

    public function update(int $id): RedirectResponse
    {
        $database = db_connect();
        $existing = $database->table('historical_blacklists')->where('id', $id)->get()->getRowArray();
        if ($existing === null) {
            return $this->failure('Data blacklist historis tidak ditemukan.');
        }
        $data = $this->validatedData($existing);
        if (is_string($data)) {
            return $this->failure($data);
        }
        if ($this->hasDuplicateIdentity($data, $id)) {
            return $this->failure('Identitas tersebut sudah digunakan oleh data blacklist historis lain.');
        }

        $now = date('Y-m-d H:i:s');
        $userId = $this->currentUserId();
        $wasActive = in_array(ApplicantBlacklistService::statusOf($existing), ['active', 'permanent'], true);
        $database->transStart();
        $database->table('historical_blacklists')->where('id', $id)->update($data + [
            'revoked_at' => null,
            'revoked_by' => null,
            'revocation_reason' => null,
            'updated_by' => $userId,
            'updated_at' => $now,
        ]);
        $this->insertHistory($id, $wasActive ? 'updated' : 'reactivated', $wasActive ? 'Data blacklist historis diperbarui.' : 'Blacklist historis diaktifkan kembali.', $userId, $now);
        $database->transComplete();

        return $database->transStatus()
            ? $this->success('Blacklist historis ' . $data['full_name'] . ' berhasil diperbarui.')
            : $this->failure('Perubahan blacklist historis gagal disimpan.');
    }

    public function revoke(int $id): RedirectResponse
    {
        $database = db_connect();
        $entry = $database->table('historical_blacklists')->where('id', $id)->get()->getRowArray();
        if ($entry === null) {
            return $this->failure('Data blacklist historis tidak ditemukan.');
        }
        if (! in_array(ApplicantBlacklistService::statusOf($entry), ['active', 'permanent'], true)) {
            return $this->failure('Blacklist sudah berakhir atau telah dicabut.');
        }
        $reason = mb_substr(trim((string) $this->request->getPost('revocation_reason')), 0, 1000);
        if (mb_strlen($reason) < 5) {
            return $this->failure('Alasan pencabutan minimal 5 karakter.');
        }

        $now = date('Y-m-d H:i:s');
        $userId = $this->currentUserId();
        $database->transStart();
        $database->table('historical_blacklists')->where('id', $id)->where('revoked_at', null)->update([
            'revoked_at' => $now,
            'revoked_by' => $userId,
            'revocation_reason' => $reason,
            'updated_by' => $userId,
            'updated_at' => $now,
        ]);
        $revoked = $database->affectedRows() === 1;
        if ($revoked) {
            $this->insertHistory($id, 'revoked', $reason, $userId, $now);
        }
        $database->transComplete();

        return $revoked && $database->transStatus()
            ? $this->success('Blacklist historis ' . $entry['full_name'] . ' berhasil dicabut.')
            : $this->failure('Blacklist historis gagal dicabut. Silakan muat ulang halaman.');
    }

    /** @param array<string, mixed>|null $existing
     *  @return array<string, mixed>|string
     */
    private function validatedData(?array $existing = null): array|string
    {
        return $this->validateValues([
            'full_name' => $this->request->getPost('full_name'),
            'nik' => $this->request->getPost('nik'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'reason' => $this->request->getPost('reason'),
            'internal_notes' => $this->request->getPost('internal_notes'),
            'source' => $this->request->getPost('source'),
            'duration' => $this->request->getPost('duration'),
            'ends_on' => $this->request->getPost('ends_on'),
        ], $existing);
    }

    /** @param array<string, mixed> $values
     *  @param array<string, mixed>|null $existing
     *  @return array<string, mixed>|string
     */
    private function validateValues(array $values, ?array $existing = null): array|string
    {
        $fullName = mb_substr(trim((string) ($values['full_name'] ?? '')), 0, 150);
        $nik = preg_replace('/\D+/', '', (string) ($values['nik'] ?? '')) ?? '';
        $emailInput = trim((string) ($values['email'] ?? ''));
        $email = HistoricalBlacklistService::normalizeEmail($emailInput);
        $phone = HistoricalBlacklistService::normalizePhone((string) ($values['phone'] ?? ''));
        $reason = mb_substr(trim((string) ($values['reason'] ?? '')), 0, 1000);
        $notes = mb_substr(trim((string) ($values['internal_notes'] ?? '')), 0, 5000);
        $source = mb_substr(trim((string) ($values['source'] ?? '')), 0, 150);
        if (mb_strlen($fullName) < 3) {
            return 'Nama lengkap minimal 3 karakter.';
        }
        if ($nik !== '' && preg_match('/\A\d{16}\z/D', $nik) !== 1) {
            return 'NIK harus terdiri dari 16 angka.';
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Format email tidak valid.';
        }
        if ($phone !== '' && preg_match('/\A62\d{8,13}\z/D', $phone) !== 1) {
            return 'Format nomor telepon tidak valid.';
        }
        $nikHash = $nik !== '' ? hash_hmac('sha256', $nik, (string) config('Encryption')->key) : ($existing['nik_hash'] ?? null);
        $nikLastFour = $nik !== '' ? substr($nik, -4) : ($existing['nik_last_four'] ?? null);
        if ($nikHash === null && $email === '' && $phone === '') {
            return 'Isi minimal satu identitas: NIK, email, atau nomor telepon.';
        }
        if (mb_strlen($reason) < 5) {
            return 'Alasan blacklist minimal 5 karakter.';
        }

        $period = $this->periodData(trim((string) ($values['duration'] ?? '')), trim((string) ($values['ends_on'] ?? '')));
        if (is_string($period)) {
            return $period;
        }

        return $period + [
            'full_name' => $fullName,
            'nik_hash' => $nikHash,
            'nik_last_four' => $nikLastFour,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'reason' => $reason,
            'internal_notes' => $notes !== '' ? $notes : null,
            'source' => $source !== '' ? $source : null,
        ];
    }

    /** @return array<string, mixed>|string */
    private function periodData(string $duration, string $endsOn): array|string
    {
        if (! isset(self::DURATION_LABELS[$duration])) {
            return 'Masa berlaku blacklist tidak valid.';
        }
        $start = new DateTimeImmutable();
        if ($duration === 'permanent') {
            return ['starts_at' => $start->format('Y-m-d H:i:s'), 'ends_at' => null, 'is_permanent' => 1];
        }
        if ($duration === 'custom') {
            $value = $endsOn;
            $end = DateTimeImmutable::createFromFormat('!Y-m-d', $value) ?: null;
            if ($end === null || $end->format('Y-m-d') !== $value) {
                return 'Tanggal berakhir blacklist tidak valid.';
            }
            $end = $end->setTime(23, 59, 59);
        } else {
            $modifier = match ($duration) {
                '1_month' => '+1 month', '3_months' => '+3 months', '6_months' => '+6 months',
                '1_year' => '+1 year', '2_years' => '+2 years',
            };
            $end = $start->modify($modifier)->setTime(23, 59, 59);
        }
        if ($end <= $start) {
            return 'Tanggal berakhir harus setelah waktu mulai blacklist.';
        }

        return ['starts_at' => $start->format('Y-m-d H:i:s'), 'ends_at' => $end->format('Y-m-d H:i:s'), 'is_permanent' => 0];
    }

    private function importDuration(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $mapping = [
            'permanen' => 'permanent', 'permanent' => 'permanent',
            '1 bulan' => '1_month', '1_month' => '1_month',
            '3 bulan' => '3_months', '3_months' => '3_months',
            '6 bulan' => '6_months', '6_months' => '6_months',
            '1 tahun' => '1_year', '1_year' => '1_year',
            '2 tahun' => '2_years', '2_years' => '2_years',
            'tanggal khusus' => 'custom', 'custom' => 'custom',
        ];

        return $mapping[$value] ?? '';
    }

    private function importDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (is_numeric($value)) {
            $serial = (int) floor((float) $value);
            return $serial > 0 ? (new DateTimeImmutable('1899-12-30'))->modify('+' . $serial . ' days')->format('Y-m-d') : '';
        }
        foreach (['!Y-m-d' => 'Y-m-d', '!d/m/Y' => 'd/m/Y', '!d-m-Y' => 'd-m-Y'] as $format => $expectedFormat) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false && $date->format($expectedFormat) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function hasDuplicateIdentity(array $data, int $exceptId = 0): bool
    {
        $builder = db_connect()->table('historical_blacklists')->select('id')->groupStart();
        $hasIdentity = false;
        foreach (['nik_hash', 'email', 'phone'] as $field) {
            if (empty($data[$field])) {
                continue;
            }
            $hasIdentity ? $builder->orWhere($field, $data[$field]) : $builder->where($field, $data[$field]);
            $hasIdentity = true;
        }
        $builder->groupEnd();
        if ($exceptId > 0) {
            $builder->where('id !=', $exceptId);
        }

        return $hasIdentity && $builder->countAllResults() > 0;
    }

    private function insertHistory(int $id, string $action, string $notes, int $userId, string $now): void
    {
        db_connect()->table('historical_blacklist_histories')->insert([
            'historical_blacklist_id' => $id,
            'action' => $action,
            'action_notes' => $notes,
            'changed_by' => $userId,
            'created_at' => $now,
        ]);
    }

    private function currentUserId(): int
    {
        return (int) (session()->get('hrd_auth')['user_id'] ?? 0);
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/blacklist-historis'))->with('historical_blacklist_success', $message);
    }

    private function failure(string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/blacklist-historis'))->withInput()->with('historical_blacklist_error', $message);
    }
}
