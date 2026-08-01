<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendVacancyManagement extends Migration
{
    private const PERMISSION_CODES = [
        'vacancies.create',
        'vacancies.update',
        'vacancies.publish',
        'vacancies.delete',
    ];

    public function up(): void
    {
        $this->forge->addColumn('vacancies', [
            'summary' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'title'],
            'job_description' => ['type' => 'TEXT', 'null' => true, 'after' => 'summary'],
            'responsibilities' => ['type' => 'TEXT', 'null' => true, 'after' => 'job_description'],
            'qualifications' => ['type' => 'TEXT', 'null' => true, 'after' => 'responsibilities'],
            'headcount' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 1, 'after' => 'maximum_age'],
            'salary_min' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'after' => 'headcount'],
            'salary_max' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'after' => 'salary_min'],
            'show_salary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'salary_max'],
            'created_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'closed_at'],
            'updated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'created_by'],
        ]);
        $this->db->query('ALTER TABLE `vacancies` ADD CONSTRAINT `fk_vacancies_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE `vacancies` ADD CONSTRAINT `fk_vacancies_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');

        $this->forge->addColumn('vacancy_screening_questions', [
            'answer_options' => ['type' => 'TEXT', 'null' => true, 'after' => 'answer_type'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'display_order'],
        ]);
        $this->db->query('ALTER TABLE `vacancy_screening_questions` MODIFY `comparison_operator` VARCHAR(30) NULL');
        $this->db->table('vacancy_screening_questions')->where('comparison_operator', 'greater_than_or_equa')->update(['comparison_operator' => 'greater_than_or_equal']);

        $this->seedPermissions();
    }

    public function down(): void
    {
        $permissionIds = array_column($this->db->table('permissions')->select('id')->whereIn('code', self::PERMISSION_CODES)->get()->getResultArray(), 'id');
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        $this->db->table('vacancy_screening_questions')->where('comparison_operator', 'greater_than_or_equal')->update(['comparison_operator' => 'greater_than_or_equa']);
        $this->db->query('ALTER TABLE `vacancy_screening_questions` MODIFY `comparison_operator` VARCHAR(20) NULL');
        $this->forge->dropColumn('vacancy_screening_questions', ['answer_options', 'is_active']);
        $this->db->query('ALTER TABLE `vacancies` DROP FOREIGN KEY `fk_vacancies_created_by`');
        $this->db->query('ALTER TABLE `vacancies` DROP FOREIGN KEY `fk_vacancies_updated_by`');
        $this->forge->dropColumn('vacancies', [
            'summary', 'job_description', 'responsibilities', 'qualifications', 'headcount',
            'salary_min', 'salary_max', 'show_salary', 'created_by', 'updated_by',
        ]);
    }

    private function seedPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['name' => 'Membuat lowongan', 'code' => 'vacancies.create', 'description' => 'Membuat lowongan kerja baru.'],
            ['name' => 'Mengubah lowongan', 'code' => 'vacancies.update', 'description' => 'Mengubah data dan screening lowongan.'],
            ['name' => 'Mempublikasikan lowongan', 'code' => 'vacancies.publish', 'description' => 'Membuka, menutup, atau mengarsipkan lowongan.'],
            ['name' => 'Menghapus lowongan', 'code' => 'vacancies.delete', 'description' => 'Menghapus lowongan yang belum memiliki pelamar.'],
        ];
        foreach ($rows as $row) {
            $this->db->table('permissions')->insert($row + ['module' => 'vacancies', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSION_CODES)->get()->getResultArray(), 'id', 'code');
        $mapping = [
            'SUPER_ADMIN' => self::PERMISSION_CODES,
            'HRD_MANAGER' => self::PERMISSION_CODES,
            'RECRUITER' => ['vacancies.create', 'vacancies.update'],
        ];
        foreach ($mapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                if (isset($roles[$roleCode], $permissions[$permissionCode])) {
                    $this->db->table('role_permissions')->insert(['role_id' => $roles[$roleCode], 'permission_id' => $permissions[$permissionCode], 'assigned_by' => null, 'assigned_at' => $now]);
                }
            }
        }
    }
}
