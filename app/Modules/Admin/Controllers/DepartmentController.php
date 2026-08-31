<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class DepartmentController extends BaseController
{
    public function index(): string
    {
        $this->disableClientCaching();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $keyword = trim((string) $this->request->getGet('keyword'));
        $builder = db_connect()->table('departments')
            ->select('departments.*, COUNT(vacancies.id) AS vacancy_count')
            ->join('vacancies', 'vacancies.department_id = departments.id', 'left')
            ->groupBy('departments.id')
            ->orderBy('departments.display_order', 'ASC')
            ->orderBy('departments.name', 'ASC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('departments.name', $keyword)
                ->orLike('departments.code', $keyword)
                ->orLike('departments.description', $keyword)
                ->groupEnd();
        }

        return view('admin/departments', [
            'auth' => $auth,
            'departments' => $builder->get()->getResultArray(),
            'keyword' => $keyword,
            'canManage' => Services::authorization()->can($userId, 'departments.manage'),
            'canDelete' => Services::authorization()->can($userId, 'departments.delete'),
            'canViewRecruitmentSettings' => Services::authorization()->can($userId, 'recruitment.settings.view'),
            'canViewVacancies' => Services::authorization()->can($userId, 'vacancies.view'),
            'success' => session()->getFlashdata('department_success'),
            'error' => session()->getFlashdata('department_error'),
            'openCreateModal' => session()->getFlashdata('department_form') === 'create',
        ]);
    }

    public function create(): RedirectResponse
    {
        $data = $this->validatedInput(true);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        if ($this->duplicateExists($data['code'], $data['name'])) {
            return $this->departmentError('Kode atau nama departemen sudah digunakan.');
        }

        $now = date('Y-m-d H:i:s');
        try {
            db_connect()->table('departments')->insert($data + ['created_at' => $now, 'updated_at' => $now]);
        } catch (DatabaseException) {
            return $this->departmentError('Departemen gagal ditambahkan. Pastikan kode dan nama belum digunakan.');
        }

        return $this->departmentSuccess('Departemen berhasil ditambahkan.');
    }

    public function update(int $departmentId): RedirectResponse
    {
        if ($this->findDepartment($departmentId) === null) {
            return $this->departmentError('Departemen tidak ditemukan.');
        }
        $data = $this->validatedInput();
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        if ($this->duplicateExists($data['code'], $data['name'], $departmentId)) {
            return $this->departmentError('Kode atau nama departemen sudah digunakan.');
        }

        try {
            db_connect()->table('departments')->where('id', $departmentId)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
        } catch (DatabaseException) {
            return $this->departmentError('Departemen gagal diperbarui. Pastikan kode dan nama belum digunakan.');
        }

        return $this->departmentSuccess('Departemen berhasil diperbarui.');
    }

    public function toggle(int $departmentId): RedirectResponse
    {
        $department = $this->findDepartment($departmentId);
        if ($department === null) {
            return $this->departmentError('Departemen tidak ditemukan.');
        }

        db_connect()->table('departments')->where('id', $departmentId)->update([
            'is_active' => (int) $department['is_active'] === 1 ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->departmentSuccess('Status departemen berhasil diubah.');
    }

    public function delete(int $departmentId): RedirectResponse
    {
        $department = $this->findDepartment($departmentId);
        if ($department === null) {
            return $this->departmentError('Departemen tidak ditemukan.');
        }
        $vacancyCount = db_connect()->table('vacancies')->where('department_id', $departmentId)->countAllResults();
        if ($vacancyCount > 0) {
            return $this->departmentError('Departemen masih digunakan oleh lowongan. Nonaktifkan departemen jika tidak ingin menampilkannya.');
        }

        try {
            db_connect()->table('departments')->where('id', $departmentId)->delete();
        } catch (DatabaseException) {
            return $this->departmentError('Departemen tidak dapat dihapus karena masih digunakan oleh data lain.');
        }

        return $this->departmentSuccess('Departemen berhasil dihapus.');
    }

    /** @return array<string, mixed>|RedirectResponse */
    private function validatedInput(bool $generateCode = false): array|RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $code = $generateCode
            ? $this->codeFromName($name)
            : mb_strtolower(trim((string) $this->request->getPost('code')));
        $description = trim((string) $this->request->getPost('description'));
        $order = (int) $this->request->getPost('display_order');

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $code) !== 1 || mb_strlen($code) > 50) {
            return $this->departmentError('Kode wajib menggunakan huruf kecil, angka, atau tanda hubung, maksimal 50 karakter.');
        }
        if ($name === '' || mb_strlen($name) > 100) {
            return $this->departmentError('Nama departemen wajib diisi dan maksimal 100 karakter.');
        }
        if (mb_strlen($description) > 500) {
            return $this->departmentError('Deskripsi maksimal 500 karakter.');
        }
        if ($order < 0 || $order > 999) {
            return $this->departmentError('Urutan departemen harus antara 0-999.');
        }

        return [
            'code' => $code,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'display_order' => $order,
            'is_active' => $this->request->getPost('is_active') !== null ? 1 : 0,
        ];
    }

    private function codeFromName(string $name): string
    {
        $normalized = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $name);
        $normalized = is_string($normalized) ? $normalized : mb_strtolower($name);
        $code = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';

        return trim(mb_substr(trim($code, '-'), 0, 50), '-');
    }

    private function duplicateExists(string $code, string $name, ?int $exceptId = null): bool
    {
        $builder = db_connect()->table('departments')
            ->groupStart()->where('code', $code)->orWhere('name', $name)->groupEnd();
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }

    /** @return array<string, mixed>|null */
    private function findDepartment(int $departmentId): ?array
    {
        return db_connect()->table('departments')->where('id', $departmentId)->get()->getRowArray();
    }

    private function departmentSuccess(string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/departemen'))->with('department_success', $message);
    }

    private function departmentError(string $message): RedirectResponse
    {
        $redirect = redirect()->to(site_url('adminhrdmannakampus/departemen'))
            ->with('department_error', $message);

        if ($this->request->getPost('form_origin') === 'create') {
            $redirect->with('department_form', 'create')->withInput();
        }

        return $redirect;
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
