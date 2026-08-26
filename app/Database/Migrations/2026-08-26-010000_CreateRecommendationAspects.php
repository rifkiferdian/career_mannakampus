<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecommendationAspects extends Migration
{
    private const PERMISSIONS = ['recommendation.aspects.view', 'recommendation.aspects.manage'];

    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'name' => ['type' => 'VARCHAR', 'constraint' => 180],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'input_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'scale_1_5'],
            'options_json' => ['type' => 'TEXT', 'null' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'display_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 1],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_recommendation_aspect_code');
        $this->forge->addKey(['is_active', 'display_order'], false, false, 'idx_recommendation_aspect_order');
        $this->forge->createTable('recommendation_aspects');

        $now = date('Y-m-d H:i:s');
        $defaults = [
            ['code' => 'pengalaman_kerja', 'name' => 'Pengalaman kerja', 'description' => 'Kesesuaian dan kedalaman pengalaman kerja kandidat.', 'input_type' => 'scale_1_5', 'options_json' => null, 'display_order' => 1],
            ['code' => 'kemampuan_teknis', 'name' => 'Kemampuan teknis', 'description' => 'Penguasaan kompetensi teknis yang dibutuhkan posisi.', 'input_type' => 'scale_1_5', 'options_json' => null, 'display_order' => 2],
            ['code' => 'komunikasi', 'name' => 'Komunikasi', 'description' => 'Kejelasan dan efektivitas komunikasi kandidat.', 'input_type' => 'scale_1_5', 'options_json' => null, 'display_order' => 3],
            ['code' => 'sikap_budaya_kerja', 'name' => 'Sikap dan budaya kerja', 'description' => 'Kesesuaian sikap kandidat dengan budaya kerja.', 'input_type' => 'scale_1_5', 'options_json' => null, 'display_order' => 4],
            ['code' => 'ekspektasi_gaji', 'name' => 'Ekspektasi gaji', 'description' => 'Kesesuaian ekspektasi gaji dengan anggaran posisi.', 'input_type' => 'choice', 'options_json' => json_encode(['Sesuai', 'Tidak sesuai'], JSON_UNESCAPED_UNICODE), 'display_order' => 5],
        ];
        foreach ($defaults as $default) {
            $this->db->table('recommendation_aspects')->insert($default + ['is_required' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        $permissionRows = [
            ['name' => 'Melihat aspek nilai', 'code' => self::PERMISSIONS[0], 'module' => 'recommendations', 'description' => 'Melihat daftar aspek penilaian kandidat.'],
            ['name' => 'Mengelola aspek nilai', 'code' => self::PERMISSIONS[1], 'module' => 'recommendations', 'description' => 'Menambah, mengubah, mengurutkan, dan menonaktifkan aspek penilaian kandidat.'],
        ];
        foreach ($permissionRows as $row) {
            $this->db->table('permissions')->insert($row + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id', 'code');
        $mapping = ['SUPER_ADMIN' => self::PERMISSIONS, 'HRD_MANAGER' => self::PERMISSIONS, 'RECRUITER' => [self::PERMISSIONS[0]]];
        foreach ($mapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                if (isset($roles[$roleCode], $permissions[$permissionCode])) {
                    $this->db->table('role_permissions')->insert(['role_id' => $roles[$roleCode], 'permission_id' => $permissions[$permissionCode], 'assigned_by' => null, 'assigned_at' => $now]);
                }
            }
        }
    }

    public function down(): void
    {
        $permissionIds = array_column($this->db->table('permissions')->select('id')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id');
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }
        $this->forge->dropTable('recommendation_aspects', true);
    }
}
