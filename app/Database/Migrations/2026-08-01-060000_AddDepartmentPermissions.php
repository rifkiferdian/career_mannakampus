<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepartmentPermissions extends Migration
{
    private const CODES = [
        'departments.view',
        'departments.manage',
        'departments.delete',
    ];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['name' => 'Melihat departemen', 'code' => 'departments.view', 'description' => 'Melihat daftar dan detail departemen.'],
            ['name' => 'Mengelola departemen', 'code' => 'departments.manage', 'description' => 'Membuat, mengubah, mengurutkan, dan menonaktifkan departemen.'],
            ['name' => 'Menghapus departemen', 'code' => 'departments.delete', 'description' => 'Menghapus departemen yang belum digunakan oleh lowongan.'],
        ];

        foreach ($rows as $row) {
            $this->db->table('permissions')->insert($row + [
                'module' => 'departments',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column(
            $this->db->table('permissions')->select('id, code')->whereIn('code', self::CODES)->get()->getResultArray(),
            'id',
            'code'
        );
        $mapping = [
            'SUPER_ADMIN' => self::CODES,
            'HRD_MANAGER' => self::CODES,
            'RECRUITER' => ['departments.view'],
        ];

        foreach ($mapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                if (isset($roles[$roleCode], $permissions[$permissionCode])) {
                    $this->db->table('role_permissions')->insert([
                        'role_id' => $roles[$roleCode],
                        'permission_id' => $permissions[$permissionCode],
                        'assigned_by' => null,
                        'assigned_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $permissionIds = array_column(
            $this->db->table('permissions')->select('id')->whereIn('code', self::CODES)->get()->getResultArray(),
            'id'
        );
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
}
