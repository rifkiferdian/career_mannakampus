<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruitmentProcessTemplates extends Migration
{
    private const PERMISSIONS = ['recruitment.templates.view', 'recruitment.templates.manage'];

    public function up(): void
    {
        $this->createTemplatesTable();
        $this->createTemplateStagesTable();
        $this->seedStagesAndTemplates();
        $this->forge->addColumn('vacancies', [
            'recruitment_process_template_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'requirement_group_id'],
        ]);
        $defaultTemplate = $this->db->table('recruitment_process_templates')->where('code', 'proses-sederhana')->get()->getRowArray();
        if ($defaultTemplate !== null) {
            $this->db->table('vacancies')->where('recruitment_process_template_id', null)->update([
                'recruitment_process_template_id' => $defaultTemplate['id'],
            ]);
        }
        $this->db->query('ALTER TABLE vacancies ADD CONSTRAINT fk_vacancies_process_template FOREIGN KEY (recruitment_process_template_id) REFERENCES recruitment_process_templates(id) ON UPDATE CASCADE ON DELETE RESTRICT');
        $this->seedPermissions();
    }

    public function down(): void
    {
        $permissionIds = array_column($this->db->table('permissions')->select('id')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id');
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }
        $this->db->query('ALTER TABLE vacancies DROP FOREIGN KEY fk_vacancies_process_template');
        $this->forge->dropColumn('vacancies', 'recruitment_process_template_id');
        $this->forge->dropTable('recruitment_process_template_stages', true);
        $this->forge->dropTable('recruitment_process_templates', true);
        $this->db->table('recruitment_stages')->whereIn('code', ['document_screening', 'written_test', 'written_test_1', 'written_test_2'])->delete();
    }

    private function createTemplatesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('is_active');
        $this->forge->createTable('recruitment_process_templates');
    }

    private function createTemplateStagesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'stage_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'display_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['template_id', 'stage_id']);
        $this->forge->addUniqueKey(['template_id', 'display_order']);
        $this->forge->addForeignKey('template_id', 'recruitment_process_templates', 'id', 'CASCADE', 'CASCADE', 'fk_process_stage_template');
        $this->forge->addForeignKey('stage_id', 'recruitment_stages', 'id', 'CASCADE', 'RESTRICT', 'fk_process_stage_stage');
        $this->forge->createTable('recruitment_process_template_stages');
    }

    private function seedStagesAndTemplates(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('recruitment_stages')->where('code', 'hrd_interview')->update(['name' => 'Wawancara HRD', 'updated_at' => $now]);
        $this->db->table('recruitment_stages')->where('code', 'user_interview')->update(['name' => 'Wawancara User', 'updated_at' => $now]);
        $nextOrder = (int) ($this->db->table('recruitment_stages')->selectMax('display_order')->get()->getRowArray()['display_order'] ?? 0) + 1;
        $newStages = [
            ['code' => 'document_screening', 'name' => 'Screening Berkas', 'color_hex' => '#2563EB', 'sla_days' => 3],
            ['code' => 'written_test', 'name' => 'Tes Tertulis', 'color_hex' => '#D97706', 'sla_days' => 3],
            ['code' => 'written_test_1', 'name' => 'Tes Tertulis Tahap 1', 'color_hex' => '#EA580C', 'sla_days' => 3],
            ['code' => 'written_test_2', 'name' => 'Tes Tertulis Tahap 2', 'color_hex' => '#C2410C', 'sla_days' => 3],
        ];
        foreach ($newStages as $stage) {
            if ($this->db->table('recruitment_stages')->where('code', $stage['code'])->countAllResults() === 0) {
                $this->db->table('recruitment_stages')->insert($stage + ['display_order' => $nextOrder++, 'is_terminal' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $templates = [
            'proses-sederhana' => ['name' => 'Tahapan 1 - Sederhana', 'description' => 'Screening Berkas, Tes Tertulis, Wawancara HRD, lalu Diterima.', 'stages' => ['document_screening', 'written_test', 'hrd_interview', 'accepted']],
            'proses-lengkap' => ['name' => 'Tahapan 2 - Lengkap', 'description' => 'Screening Berkas, dua tahap Tes Tertulis, Wawancara HRD, Wawancara User, lalu Diterima.', 'stages' => ['document_screening', 'written_test_1', 'written_test_2', 'hrd_interview', 'user_interview', 'accepted']],
        ];
        $stageIds = array_column($this->db->table('recruitment_stages')->select('id, code')->get()->getResultArray(), 'id', 'code');
        foreach ($templates as $code => $template) {
            $this->db->table('recruitment_process_templates')->insert(['code' => $code, 'name' => $template['name'], 'description' => $template['description'], 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $templateId = (int) $this->db->insertID();
            foreach ($template['stages'] as $index => $stageCode) {
                $this->db->table('recruitment_process_template_stages')->insert(['template_id' => $templateId, 'stage_id' => $stageIds[$stageCode], 'display_order' => $index + 1, 'created_at' => $now]);
            }
        }
    }

    private function seedPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['name' => 'Melihat template tahapan', 'code' => self::PERMISSIONS[0], 'module' => 'recruitment_templates', 'description' => 'Melihat template urutan proses rekrutmen.'],
            ['name' => 'Mengelola template tahapan', 'code' => self::PERMISSIONS[1], 'module' => 'recruitment_templates', 'description' => 'Membuat, mengubah, dan menghapus template urutan proses rekrutmen.'],
        ];
        foreach ($rows as $row) {
            if ($this->db->table('permissions')->where('code', $row['code'])->countAllResults() === 0) {
                $this->db->table('permissions')->insert($row + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id', 'code');
        $mapping = ['SUPER_ADMIN' => self::PERMISSIONS, 'HRD_MANAGER' => self::PERMISSIONS, 'RECRUITER' => [self::PERMISSIONS[0]]];
        foreach ($mapping as $roleCode => $codes) {
            foreach ($codes as $code) {
                if (isset($roles[$roleCode], $permissions[$code]) && $this->db->table('role_permissions')->where('role_id', $roles[$roleCode])->where('permission_id', $permissions[$code])->countAllResults() === 0) {
                    $this->db->table('role_permissions')->insert(['role_id' => $roles[$roleCode], 'permission_id' => $permissions[$code], 'assigned_by' => null, 'assigned_at' => $now]);
                }
            }
        }
    }
}
