<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVacancyRecruitmentPeriods extends Migration
{
    private const PERMISSION_CODES = [
        'vacancy.periods.view',
        'vacancy.periods.manage',
        'vacancy.periods.publish',
    ];

    public function up(): void
    {
        $this->createPeriodsTable();
        $this->backfillPeriods();

        $this->forge->addColumn('applications', [
            'vacancy_period_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'after' => 'vacancy_id',
            ],
        ]);

        $this->db->query(
            'UPDATE `applications` AS `applications` '
            . 'INNER JOIN `vacancy_recruitment_periods` AS `periods` '
            . 'ON `periods`.`vacancy_id` = `applications`.`vacancy_id` AND `periods`.`is_initial` = 1 '
            . 'SET `applications`.`vacancy_period_id` = `periods`.`id` '
            . 'WHERE `applications`.`vacancy_period_id` IS NULL'
        );
        $this->db->query('ALTER TABLE `applications` ADD INDEX `idx_applications_applicant` (`applicant_id`)');
        $this->db->query('ALTER TABLE `applications` DROP INDEX `applicant_id_vacancy_id`');
        $this->db->query(
            'ALTER TABLE `applications` '
            . 'MODIFY `vacancy_period_id` BIGINT(20) UNSIGNED NOT NULL, '
            . 'ADD UNIQUE INDEX `uq_applicant_vacancy_period` (`applicant_id`, `vacancy_period_id`), '
            . 'ADD INDEX `idx_applications_vacancy_period` (`vacancy_period_id`), '
            . 'ADD CONSTRAINT `fk_applications_vacancy_period` FOREIGN KEY (`vacancy_period_id`) '
            . 'REFERENCES `vacancy_recruitment_periods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE'
        );

        $this->seedPermissions();
    }

    public function down(): void
    {
        $permissionIds = array_column(
            $this->db->table('permissions')->select('id')->whereIn('code', self::PERMISSION_CODES)->get()->getResultArray(),
            'id'
        );
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        $this->db->query('ALTER TABLE `applications` DROP FOREIGN KEY `fk_applications_vacancy_period`');
        $this->db->query('ALTER TABLE `applications` DROP INDEX `uq_applicant_vacancy_period`');
        $this->db->query('ALTER TABLE `applications` DROP INDEX `idx_applications_vacancy_period`');
        $this->forge->dropColumn('applications', 'vacancy_period_id');

        $duplicates = $this->db->query(
            'SELECT COUNT(*) AS `total` FROM ('
            . 'SELECT `applicant_id`, `vacancy_id` FROM `applications` '
            . 'GROUP BY `applicant_id`, `vacancy_id` HAVING COUNT(*) > 1'
            . ') AS `duplicates`'
        )->getRowArray();
        if ((int) ($duplicates['total'] ?? 0) === 0) {
            $this->db->query('ALTER TABLE `applications` ADD UNIQUE INDEX `applicant_id_vacancy_id` (`applicant_id`, `vacancy_id`)');
        } else {
            $this->db->query('ALTER TABLE `applications` ADD INDEX `idx_applications_applicant_vacancy` (`applicant_id`, `vacancy_id`)');
        }
        $this->db->query('ALTER TABLE `applications` DROP INDEX `idx_applications_applicant`');

        $this->forge->dropTable('vacancy_recruitment_periods', true);
    }

    private function createPeriodsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'vacancy_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'period_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'period_code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'opened_at' => ['type' => 'DATETIME', 'null' => true],
            'closed_at' => ['type' => 'DATETIME', 'null' => true],
            'headcount' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 1],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'is_initial' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['vacancy_id', 'period_code'], 'uq_vacancy_period_code');
        $this->forge->addKey(['vacancy_id', 'status'], false, false, 'idx_vacancy_period_status');
        $this->forge->addKey(['opened_at', 'closed_at'], false, false, 'idx_vacancy_period_dates');
        $this->forge->addForeignKey('vacancy_id', 'vacancies', 'id', 'CASCADE', 'RESTRICT', 'fk_periods_vacancy');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_periods_created_by');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_periods_updated_by');
        $this->forge->createTable('vacancy_recruitment_periods');
    }

    private function backfillPeriods(): void
    {
        $now = date('Y-m-d H:i:s');
        $vacancies = $this->db->table('vacancies')->where('deleted_at', null)->get()->getResultArray();
        foreach ($vacancies as $vacancy) {
            $this->db->table('vacancy_recruitment_periods')->insert([
                'vacancy_id' => (int) $vacancy['id'],
                'period_name' => 'Periode Awal',
                'period_code' => 'awal-' . (int) $vacancy['id'],
                'opened_at' => $vacancy['opened_at'] ?: null,
                'closed_at' => $vacancy['closed_at'] ?: null,
                'headcount' => max(1, (int) ($vacancy['headcount'] ?? 1)),
                'status' => in_array($vacancy['status'], ['draft', 'open', 'closed', 'archived'], true) ? $vacancy['status'] : 'draft',
                'notes' => 'Dibuat otomatis dari data lowongan sebelum fitur periode rekrutmen.',
                'is_initial' => 1,
                'created_by' => $vacancy['created_by'] ?: null,
                'updated_by' => $vacancy['updated_by'] ?: null,
                'created_at' => $vacancy['created_at'] ?: $now,
                'updated_at' => $vacancy['updated_at'] ?: $now,
            ]);
        }
    }

    private function seedPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['name' => 'Melihat periode rekrutmen', 'code' => 'vacancy.periods.view', 'description' => 'Melihat sesi atau periode pembukaan setiap lowongan.'],
            ['name' => 'Mengelola periode rekrutmen', 'code' => 'vacancy.periods.manage', 'description' => 'Membuat, mengubah, dan menghapus periode rekrutmen.'],
            ['name' => 'Menerbitkan periode rekrutmen', 'code' => 'vacancy.periods.publish', 'description' => 'Membuka, menutup, dan mengarsipkan periode rekrutmen.'],
        ];
        foreach ($rows as $row) {
            $existing = $this->db->table('permissions')->where('code', $row['code'])->get()->getRowArray();
            $data = $row + ['module' => 'vacancies', 'is_active' => 1, 'updated_at' => $now];
            if ($existing === null) {
                $this->db->table('permissions')->insert($data + ['created_at' => $now]);
            } else {
                $this->db->table('permissions')->where('id', $existing['id'])->update($data);
            }
        }

        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSION_CODES)->get()->getResultArray(), 'id', 'code');
        $mapping = [
            'SUPER_ADMIN' => self::PERMISSION_CODES,
            'HRD_MANAGER' => self::PERMISSION_CODES,
            'RECRUITER' => ['vacancy.periods.view', 'vacancy.periods.manage'],
            'VIEWER' => ['vacancy.periods.view'],
        ];
        foreach ($mapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                if (! isset($roles[$roleCode], $permissions[$permissionCode])) {
                    continue;
                }
                $exists = $this->db->table('role_permissions')->where('role_id', $roles[$roleCode])->where('permission_id', $permissions[$permissionCode])->countAllResults() > 0;
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
