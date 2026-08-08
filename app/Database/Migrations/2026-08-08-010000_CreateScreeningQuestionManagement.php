<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateScreeningQuestionManagement extends Migration
{
    private const PERMISSION_CODES = [
        'screening.questions.view',
        'screening.defaults.manage',
        'screening.vacancies.manage',
    ];

    public function up(): void
    {
        $this->forge->addColumn('vacancy_screening_questions', [
            'source_default_question_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'after' => 'vacancy_id',
            ],
        ]);
        $this->db->query(
            'ALTER TABLE `vacancy_screening_questions` '
            . 'ADD INDEX `idx_vacancy_screening_source` (`source_default_question_id`), '
            . 'ADD CONSTRAINT `fk_vacancy_screening_source` FOREIGN KEY (`source_default_question_id`) '
            . 'REFERENCES `default_screening_questions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE'
        );
        $this->db->query(
            'UPDATE `vacancy_screening_questions` AS `questions` '
            . 'INNER JOIN `default_screening_questions` AS `defaults` '
            . 'ON `defaults`.`question_code` = `questions`.`question_code` '
            . 'SET `questions`.`source_default_question_id` = `defaults`.`id` '
            . 'WHERE `questions`.`source_default_question_id` IS NULL'
        );

        $this->seedPermissions();
    }

    public function down(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('permissions')->where('code', 'recruitment.settings.view')->update([
            'description' => 'Melihat tahapan, template penolakan, dan screening default.',
            'updated_at' => $now,
        ]);
        $this->db->table('permissions')->where('code', 'recruitment.settings.manage')->update([
            'description' => 'Mengubah tahapan, template penolakan, dan screening default.',
            'updated_at' => $now,
        ]);
        $permissionIds = array_column(
            $this->db->table('permissions')->select('id')->whereIn('code', self::PERMISSION_CODES)->get()->getResultArray(),
            'id'
        );
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        $this->db->query('ALTER TABLE `vacancy_screening_questions` DROP FOREIGN KEY `fk_vacancy_screening_source`');
        $this->db->query('ALTER TABLE `vacancy_screening_questions` DROP INDEX `idx_vacancy_screening_source`');
        $this->forge->dropColumn('vacancy_screening_questions', 'source_default_question_id');
    }

    private function seedPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('permissions')->where('code', 'recruitment.settings.view')->update([
            'description' => 'Melihat tahapan seleksi dan template alasan penolakan.',
            'updated_at' => $now,
        ]);
        $this->db->table('permissions')->where('code', 'recruitment.settings.manage')->update([
            'description' => 'Mengubah tahapan seleksi dan template alasan penolakan.',
            'updated_at' => $now,
        ]);
        $rows = [
            ['name' => 'Melihat pertanyaan screening', 'code' => 'screening.questions.view', 'description' => 'Melihat bank pertanyaan default dan screening setiap lowongan.'],
            ['name' => 'Mengelola screening default', 'code' => 'screening.defaults.manage', 'description' => 'Membuat, mengubah, dan menghapus pertanyaan screening default.'],
            ['name' => 'Mengelola screening lowongan', 'code' => 'screening.vacancies.manage', 'description' => 'Menyalin default serta mengelola pertanyaan khusus setiap lowongan.'],
        ];

        foreach ($rows as $row) {
            $existing = $this->db->table('permissions')->where('code', $row['code'])->get()->getRowArray();
            $data = $row + ['module' => 'screening', 'is_active' => 1, 'updated_at' => $now];
            if ($existing === null) {
                $this->db->table('permissions')->insert($data + ['created_at' => $now]);
            } else {
                $this->db->table('permissions')->where('id', $existing['id'])->update($data);
            }
        }

        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column(
            $this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSION_CODES)->get()->getResultArray(),
            'id',
            'code'
        );
        $mapping = [
            'SUPER_ADMIN' => self::PERMISSION_CODES,
            'HRD_MANAGER' => self::PERMISSION_CODES,
            'RECRUITER' => ['screening.questions.view', 'screening.vacancies.manage'],
        ];

        foreach ($mapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                if (! isset($roles[$roleCode], $permissions[$permissionCode])) {
                    continue;
                }
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roles[$roleCode])
                    ->where('permission_id', $permissions[$permissionCode])
                    ->countAllResults() > 0;
                if (! $exists) {
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
