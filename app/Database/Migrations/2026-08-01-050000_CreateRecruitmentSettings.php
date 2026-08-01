<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruitmentSettings extends Migration
{
    public function up(): void
    {
        $this->createStagesTable();
        $this->createRejectionTemplatesTable();
        $this->createDefaultScreeningTable();
        $this->seedReferenceData();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $permissionIds = array_column(
            $this->db->table('permissions')
                ->select('id')
                ->whereIn('code', ['recruitment.settings.view', 'recruitment.settings.manage'])
                ->get()
                ->getResultArray(),
            'id'
        );
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        $this->forge->dropTable('default_screening_questions', true);
        $this->forge->dropTable('rejection_reason_templates', true);
        $this->forge->dropTable('recruitment_stages', true);
    }

    private function createStagesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'color_hex' => ['type' => 'CHAR', 'constraint' => 7],
            'display_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'sla_days' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'is_terminal' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('display_order');
        $this->forge->addKey('is_active');
        $this->forge->createTable('recruitment_stages');
    }

    private function createRejectionTemplatesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'reason_text' => ['type' => 'VARCHAR', 'constraint' => 1000],
            'display_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'display_order']);
        $this->forge->createTable('rejection_reason_templates');
    }

    private function createDefaultScreeningTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'question_code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'question_text' => ['type' => 'VARCHAR', 'constraint' => 500],
            'answer_type' => ['type' => 'VARCHAR', 'constraint' => 20],
            'answer_options' => ['type' => 'TEXT', 'null' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_knockout' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'expected_value' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'comparison_operator' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'display_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('question_code');
        $this->forge->addKey(['is_active', 'display_order']);
        $this->forge->createTable('default_screening_questions');
    }

    private function seedReferenceData(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('recruitment_stages')->insertBatch([
            ['code' => 'administration', 'name' => 'Administrasi', 'color_hex' => '#3B82F6', 'display_order' => 1, 'sla_days' => 3, 'is_terminal' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hrd_interview', 'name' => 'Interview HRD', 'color_hex' => '#8B5CF6', 'display_order' => 2, 'sla_days' => 3, 'is_terminal' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'user_interview', 'name' => 'Interview User', 'color_hex' => '#6366F1', 'display_order' => 3, 'sla_days' => 5, 'is_terminal' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'psychotest', 'name' => 'Psikotes', 'color_hex' => '#F59E0B', 'display_order' => 4, 'sla_days' => 3, 'is_terminal' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'medical_checkup', 'name' => 'Medical Check-up', 'color_hex' => '#06B6D4', 'display_order' => 5, 'sla_days' => 5, 'is_terminal' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'accepted', 'name' => 'Diterima', 'color_hex' => '#16A34A', 'display_order' => 6, 'sla_days' => 0, 'is_terminal' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'rejected', 'name' => 'Ditolak', 'color_hex' => '#DC2626', 'display_order' => 7, 'sla_days' => 0, 'is_terminal' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->db->table('rejection_reason_templates')->insertBatch([
            ['title' => 'Kualifikasi belum sesuai', 'reason_text' => 'Terima kasih atas minat Anda. Setelah mempertimbangkan profil dan kualifikasi, kami belum dapat melanjutkan lamaran Anda untuk posisi ini.', 'display_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Belum lolos tahap seleksi', 'reason_text' => 'Terima kasih telah mengikuti proses seleksi. Berdasarkan hasil evaluasi pada tahap ini, kami belum dapat melanjutkan proses lamaran Anda.', 'display_order' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Posisi telah terisi', 'reason_text' => 'Terima kasih atas waktu dan ketertarikan Anda. Saat ini posisi yang dilamar telah terisi, namun profil Anda dapat kami pertimbangkan untuk kesempatan berikutnya.', 'display_order' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->db->table('default_screening_questions')->insertBatch([
            ['question_code' => 'willing_shift', 'question_text' => 'Apakah Anda bersedia bekerja dengan sistem shift?', 'answer_type' => 'yes_no', 'answer_options' => json_encode(['YA', 'TIDAK']), 'is_required' => 1, 'is_knockout' => 1, 'expected_value' => 'YA', 'comparison_operator' => 'equals', 'display_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['question_code' => 'willing_placement', 'question_text' => 'Apakah Anda bersedia ditempatkan sesuai kebutuhan perusahaan?', 'answer_type' => 'yes_no', 'answer_options' => json_encode(['YA', 'TIDAK']), 'is_required' => 1, 'is_knockout' => 1, 'expected_value' => 'YA', 'comparison_operator' => 'equals', 'display_order' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['question_code' => 'availability', 'question_text' => 'Berapa hari yang Anda butuhkan sebelum dapat mulai bekerja?', 'answer_type' => 'number', 'answer_options' => null, 'is_required' => 1, 'is_knockout' => 0, 'expected_value' => null, 'comparison_operator' => null, 'display_order' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $permissionRows = [
            ['name' => 'Melihat pengaturan rekrutmen', 'code' => 'recruitment.settings.view', 'module' => 'recruitment_settings', 'description' => 'Melihat tahapan, template penolakan, dan screening default.'],
            ['name' => 'Mengelola pengaturan rekrutmen', 'code' => 'recruitment.settings.manage', 'module' => 'recruitment_settings', 'description' => 'Mengubah tahapan, template penolakan, dan screening default.'],
        ];
        foreach ($permissionRows as $permission) {
            $this->db->table('permissions')->insert($permission + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', array_column($permissionRows, 'code'))->get()->getResultArray(), 'id', 'code');
        $mapping = [
            'SUPER_ADMIN' => ['recruitment.settings.view', 'recruitment.settings.manage'],
            'HRD_MANAGER' => ['recruitment.settings.view', 'recruitment.settings.manage'],
            'RECRUITER'   => ['recruitment.settings.view'],
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
}
