<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ConfigureHrdRolesAndPermissions extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->transStart();

        $legacyHrd = $this->db->table('roles')->where('code', 'HRD')->get()->getRowArray();
        if ($legacyHrd !== null) {
            $this->db->table('roles')->where('id', $legacyHrd['id'])->update([
                'name'        => 'HRD Manager',
                'code'        => 'HRD_MANAGER',
                'description' => 'Mengelola operasional rekrutmen dan tim recruiter.',
                'updated_at'  => $now,
            ]);
        }

        foreach ($this->roles() as $role) {
            $existing = $this->db->table('roles')->where('code', $role['code'])->get()->getRowArray();
            if ($existing === null) {
                $this->db->table('roles')->insert($role + ['created_at' => $now, 'updated_at' => $now]);
            } else {
                $this->db->table('roles')->where('id', $existing['id'])->update([
                    'name'        => $role['name'],
                    'description' => $role['description'],
                    'is_active'   => 1,
                    'updated_at'  => $now,
                ]);
            }
        }

        foreach ($this->permissions() as $permission) {
            $existing = $this->db->table('permissions')->where('code', $permission['code'])->get()->getRowArray();
            if ($existing === null) {
                $this->db->table('permissions')->insert($permission + ['created_at' => $now, 'updated_at' => $now]);
            } else {
                $this->db->table('permissions')->where('id', $existing['id'])->update([
                    'name'        => $permission['name'],
                    'module'      => $permission['module'],
                    'description' => $permission['description'],
                    'is_active'   => 1,
                    'updated_at'  => $now,
                ]);
            }
        }

        $roles = array_column(
            $this->db->table('roles')->select('id, code')->whereIn('code', array_keys($this->roleDefaults()))->get()->getResultArray(),
            'id',
            'code',
        );
        $permissions = array_column(
            $this->db->table('permissions')->select('id, code')->where('is_active', 1)->get()->getResultArray(),
            'id',
            'code',
        );

        foreach ($this->roleDefaults() as $roleCode => $permissionCodes) {
            if (! isset($roles[$roleCode])) {
                continue;
            }

            if ($roleCode === 'SUPER_ADMIN') {
                $permissionCodes = array_keys($permissions);
            }

            foreach ($permissionCodes as $permissionCode) {
                if (! isset($permissions[$permissionCode])) {
                    continue;
                }

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roles[$roleCode])
                    ->where('permission_id', $permissions[$permissionCode])
                    ->countAllResults() > 0;

                if (! $exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'      => $roles[$roleCode],
                        'permission_id' => $permissions[$permissionCode],
                        'assigned_by'  => null,
                        'assigned_at'  => $now,
                    ]);
                }
            }
        }

        $this->db->transComplete();
    }

    public function down(): void
    {
        // Access-control reference data is intentionally preserved on rollback.
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function roles(): array
    {
        return [
            ['name' => 'Super Administrator', 'code' => 'SUPER_ADMIN', 'description' => 'Akses penuh dan pengelolaan user, role, serta permission.', 'is_system' => 1, 'is_active' => 1],
            ['name' => 'HRD Manager', 'code' => 'HRD_MANAGER', 'description' => 'Mengelola operasional rekrutmen dan tim recruiter.', 'is_system' => 1, 'is_active' => 1],
            ['name' => 'Recruiter', 'code' => 'RECRUITER', 'description' => 'Memproses kandidat dan menjalankan tahapan rekrutmen.', 'is_system' => 1, 'is_active' => 1],
            ['name' => 'Viewer', 'code' => 'VIEWER', 'description' => 'Akses baca terbatas ke informasi rekrutmen.', 'is_system' => 1, 'is_active' => 1],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function permissions(): array
    {
        return [
            ['name' => 'Melihat kandidat', 'code' => 'candidates.view', 'module' => 'candidates', 'description' => 'Melihat daftar dan detail kandidat.', 'is_active' => 1],
            ['name' => 'Mengubah status kandidat', 'code' => 'candidates.status.update', 'module' => 'candidates', 'description' => 'Memindahkan kandidat ke tahapan seleksi berikutnya.', 'is_active' => 1],
            ['name' => 'Mengunduh CV kandidat', 'code' => 'candidates.cv.download', 'module' => 'candidates', 'description' => 'Mengunduh dokumen CV kandidat.', 'is_active' => 1],
            ['name' => 'Melihat lowongan', 'code' => 'vacancies.view', 'module' => 'vacancies', 'description' => 'Melihat daftar dan detail lowongan.', 'is_active' => 1],
            ['name' => 'Mengelola lowongan', 'code' => 'vacancies.manage', 'module' => 'vacancies', 'description' => 'Membuat, memperbarui, membuka, dan menutup lowongan.', 'is_active' => 1],
            ['name' => 'Melihat laporan rekrutmen', 'code' => 'reports.view', 'module' => 'reports', 'description' => 'Melihat dan mengekspor laporan rekrutmen.', 'is_active' => 1],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function roleDefaults(): array
    {
        $profile = ['dashboard.admin.view', 'profile.own.view', 'profile.own.update', 'profile.own.password.update'];

        return [
            'SUPER_ADMIN' => [],
            'HRD_MANAGER' => [...$profile, 'candidates.view', 'candidates.status.update', 'candidates.cv.download', 'vacancies.view', 'vacancies.manage', 'reports.view'],
            'RECRUITER'   => [...$profile, 'candidates.view', 'candidates.status.update', 'candidates.cv.download', 'vacancies.view'],
            'VIEWER'      => [...$profile, 'candidates.view', 'vacancies.view', 'reports.view'],
        ];
    }
}
