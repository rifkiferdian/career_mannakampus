<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\UserModel;
use App\Modules\Admin\Services\AuthorizationService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class AccessController extends BaseController
{
    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $roles = $database->table('roles')
            ->whereIn('code', AuthorizationService::PORTAL_ROLES)
            ->where('is_active', 1)
            ->orderBy("CASE code WHEN 'SUPER_ADMIN' THEN 1 WHEN 'HRD_MANAGER' THEN 2 WHEN 'RECRUITER' THEN 3 ELSE 4 END", '', false)
            ->get()
            ->getResultArray();
        $users = $database->table('users')
            ->select('id, full_name, email, phone, is_active, last_login_at, created_at')
            ->where('deleted_at', null)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
        $userRoles = $database->table('user_roles')
            ->select('user_roles.user_id, roles.id AS role_id, roles.name AS role_name, roles.code AS role_code')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->whereIn('roles.code', AuthorizationService::PORTAL_ROLES)
            ->orderBy("CASE roles.code WHEN 'SUPER_ADMIN' THEN 1 WHEN 'HRD_MANAGER' THEN 2 WHEN 'RECRUITER' THEN 3 ELSE 4 END", '', false)
            ->get()
            ->getResultArray();
        $rolesByUser = [];
        foreach ($userRoles as $role) {
            $rolesByUser[(int) $role['user_id']] ??= $role;
        }
        foreach ($users as &$user) {
            $user['role_id'] = $rolesByUser[(int) $user['id']]['role_id'] ?? null;
            $user['role_name'] = $rolesByUser[(int) $user['id']]['role_name'] ?? 'Tanpa role';
            $user['role_code'] = $rolesByUser[(int) $user['id']]['role_code'] ?? '';
        }
        unset($user);

        $permissions = $this->assignablePermissions();
        $rolePermissionRows = $database->table('role_permissions')
            ->select('role_id, permission_id')
            ->get()
            ->getResultArray();
        $rolePermissions = [];
        foreach ($rolePermissionRows as $mapping) {
            $rolePermissions[(int) $mapping['role_id']][] = (int) $mapping['permission_id'];
        }

        return view('admin/access', [
            'auth'            => session()->get('hrd_auth'),
            'users'           => $users,
            'roles'           => $roles,
            'permissions'     => $permissions,
            'rolePermissions' => $rolePermissions,
            'success'         => session()->getFlashdata('access_success'),
            'error'           => session()->getFlashdata('access_error'),
            'createErrors'    => session()->getFlashdata('create_user_errors') ?? [],
        ]);
    }

    public function createUser(): RedirectResponse
    {
        $input = [
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'email'     => mb_strtolower(trim((string) $this->request->getPost('email'))),
            'phone'     => trim((string) $this->request->getPost('phone')),
            'role_id'   => (int) $this->request->getPost('role_id'),
            'password'  => (string) $this->request->getPost('password'),
        ];
        $rules = [
            'full_name' => 'required|min_length[3]|max_length[150]',
            'email'     => 'required|valid_email|max_length[150]',
            'phone'     => 'permit_empty|max_length[30]|regex_match[/^\+?[0-9][0-9\s-]{7,29}$/]',
            'role_id'   => 'required|is_natural_no_zero',
            'password'  => 'required|min_length[12]|max_length[255]',
        ];

        if (! $this->validateData($input, $rules)) {
            return redirect()->to(site_url('adminhrdmannakampus/akses#new-user'))
                ->withInput()
                ->with('create_user_errors', $this->validator->getErrors());
        }

        if (! $this->isStrongPassword($input['password'])) {
            return redirect()->to(site_url('adminhrdmannakampus/akses#new-user'))
                ->withInput()
                ->with('create_user_errors', ['password' => 'Password harus memiliki huruf besar, huruf kecil, angka, dan simbol.']);
        }

        $role = db_connect()->table('roles')
            ->where('id', $input['role_id'])
            ->whereIn('code', ['HRD_MANAGER', 'RECRUITER', 'VIEWER'])
            ->where('is_active', 1)
            ->get()
            ->getRowArray();
        if ($role === null) {
            return redirect()->to(site_url('adminhrdmannakampus/akses#new-user'))
                ->withInput()
                ->with('create_user_errors', ['role_id' => 'Role yang dipilih tidak valid.']);
        }

        $userModel = new UserModel();
        if ($userModel->withDeleted()->where('email', $input['email'])->first() !== null) {
            return redirect()->to(site_url('adminhrdmannakampus/akses#new-user'))
                ->withInput()
                ->with('create_user_errors', ['email' => 'Email sudah digunakan.']);
        }

        $now = date('Y-m-d H:i:s');
        $database = db_connect();
        $database->transStart();
        $database->table('users')->insert([
            'uuid'              => $this->uuidV4(),
            'full_name'         => $input['full_name'],
            'email'             => $input['email'],
            'phone'             => $input['phone'] !== '' ? $input['phone'] : null,
            'password_hash'     => password_hash($input['password'], PASSWORD_DEFAULT),
            'email_verified_at' => $now,
            'is_active'         => 1,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $userId = (int) $database->insertID();
        $database->table('user_roles')->insert([
            'user_id'     => $userId,
            'role_id'     => $role['id'],
            'assigned_by' => $this->currentUserId(),
            'assigned_at' => $now,
        ]);
        $database->transComplete();

        return redirect()->to(site_url('adminhrdmannakampus/akses'))
            ->with('access_success', 'Akun ' . $input['full_name'] . ' berhasil dibuat sebagai ' . $role['name'] . '.');
    }

    public function updateStatus(int $userId): RedirectResponse
    {
        $authUserId = $this->currentUserId();
        if ($userId === $authUserId) {
            return $this->accessError('Anda tidak dapat menonaktifkan akun yang sedang digunakan.');
        }

        $user = db_connect()->table('users')->where('id', $userId)->where('deleted_at', null)->get()->getRowArray();
        if ($user === null) {
            return $this->accessError('Akun tidak ditemukan.');
        }

        $willActivate = ! (bool) $user['is_active'];
        if (! $willActivate && Services::authorization()->isSuperAdmin($userId) && $this->activeSuperAdminCount() <= 1) {
            return $this->accessError('Super Admin aktif terakhir tidak dapat dinonaktifkan.');
        }

        db_connect()->table('users')->where('id', $userId)->update([
            'is_active'  => $willActivate ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if (! $willActivate) {
            Services::hrdSession()->revokeAll($userId);
        }

        return redirect()->to(site_url('adminhrdmannakampus/akses'))
            ->with('access_success', 'Akun berhasil ' . ($willActivate ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    public function updateRole(int $userId): RedirectResponse
    {
        $authUserId = $this->currentUserId();
        if ($userId === $authUserId) {
            return $this->accessError('Anda tidak dapat mengubah role akun yang sedang digunakan.');
        }

        $newRoleId = (int) $this->request->getPost('role_id');
        $role = db_connect()->table('roles')
            ->where('id', $newRoleId)
            ->whereIn('code', AuthorizationService::PORTAL_ROLES)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();
        $user = db_connect()->table('users')->where('id', $userId)->where('deleted_at', null)->get()->getRowArray();
        if ($role === null || $user === null) {
            return $this->accessError('Akun atau role tidak valid.');
        }

        if (Services::authorization()->isSuperAdmin($userId) && $role['code'] !== 'SUPER_ADMIN' && $this->activeSuperAdminCount() <= 1) {
            return $this->accessError('Role Super Admin aktif terakhir tidak dapat diturunkan.');
        }

        $database = db_connect();
        $portalRoleIds = array_column(
            $database->table('roles')->select('id')->whereIn('code', AuthorizationService::PORTAL_ROLES)->get()->getResultArray(),
            'id',
        );
        $database->transStart();
        $database->table('user_roles')->where('user_id', $userId)->whereIn('role_id', $portalRoleIds)->delete();
        $database->table('user_roles')->insert([
            'user_id'     => $userId,
            'role_id'     => $newRoleId,
            'assigned_by' => $authUserId,
            'assigned_at' => date('Y-m-d H:i:s'),
        ]);
        $database->transComplete();
        Services::hrdSession()->revokeAll($userId);

        return redirect()->to(site_url('adminhrdmannakampus/akses'))
            ->with('access_success', 'Role ' . $user['full_name'] . ' berhasil diubah menjadi ' . $role['name'] . '.');
    }

    public function updatePermissions(int $roleId): RedirectResponse
    {
        $role = db_connect()->table('roles')->where('id', $roleId)->whereIn('code', AuthorizationService::PORTAL_ROLES)->get()->getRowArray();
        if ($role === null || $role['code'] === 'SUPER_ADMIN') {
            return $this->accessError('Permission Super Admin tidak dapat dibatasi.');
        }

        $requestedIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('permissions'))));
        $assignableIds = array_map('intval', array_column($this->assignablePermissions(), 'id'));
        $selectedIds = array_values(array_intersect($requestedIds, $assignableIds));
        $database = db_connect();
        $database->transStart();
        if ($assignableIds !== []) {
            $database->table('role_permissions')->where('role_id', $roleId)->whereIn('permission_id', $assignableIds)->delete();
        }
        foreach ($selectedIds as $permissionId) {
            $database->table('role_permissions')->insert([
                'role_id'      => $roleId,
                'permission_id' => $permissionId,
                'assigned_by'  => $this->currentUserId(),
                'assigned_at'  => date('Y-m-d H:i:s'),
            ]);
        }
        $database->transComplete();

        return redirect()->to(site_url('adminhrdmannakampus/akses#permissions'))
            ->with('access_success', 'Permission role ' . $role['name'] . ' berhasil diperbarui.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function assignablePermissions(): array
    {
        return db_connect()->table('permissions')
            ->whereIn('module', ['dashboard', 'candidates', 'vacancies', 'reports'])
            ->where('is_active', 1)
            ->orderBy("CASE module WHEN 'dashboard' THEN 1 WHEN 'candidates' THEN 2 WHEN 'vacancies' THEN 3 ELSE 4 END", '', false)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function activeSuperAdminCount(): int
    {
        return db_connect()->table('users')
            ->join('user_roles', 'user_roles.user_id = users.id')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->where('roles.code', 'SUPER_ADMIN')
            ->where('roles.is_active', 1)
            ->countAllResults();
    }

    private function isStrongPassword(string $password): bool
    {
        return preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^a-zA-Z0-9]/', $password) === 1;
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }

    private function accessError(string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/akses'))->with('access_error', $message);
    }

    private function currentUserId(): int
    {
        $auth = session()->get('hrd_auth');

        return is_array($auth) ? (int) ($auth['user_id'] ?? 0) : 0;
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
