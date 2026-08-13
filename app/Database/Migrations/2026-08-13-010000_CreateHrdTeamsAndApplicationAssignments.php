<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHrdTeamsAndApplicationAssignments extends Migration
{
    private const PERMISSION_CODES = [
        'hrd.teams.view',
        'hrd.teams.manage',
        'applicants.pool.view',
        'applicants.assign',
    ];

    public function up(): void
    {
        $this->createTeamsTable();
        $this->createTeamUsersTable();
        $this->addApplicationAssignmentColumns();
        $this->createAssignmentHistoriesTable();
        $this->seedTeams();
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

        $this->forge->dropTable('application_assignment_histories', true);
        $this->db->query('ALTER TABLE `applications` DROP FOREIGN KEY `fk_applications_assigned_team`');
        $this->db->query('ALTER TABLE `applications` DROP FOREIGN KEY `fk_applications_assigned_user`');
        $this->db->query('ALTER TABLE `applications` DROP INDEX `idx_applications_assigned_team`');
        $this->db->query('ALTER TABLE `applications` DROP INDEX `idx_applications_assigned_user`');
        $this->forge->dropColumn('applications', ['assigned_hrd_team_id', 'assigned_by_user_id', 'assigned_at']);
        $this->forge->dropTable('hrd_team_users', true);
        $this->forge->dropTable('hrd_teams', true);
    }

    private function createTeamsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_hrd_teams_code');
        $this->forge->addKey('is_active', false, false, 'idx_hrd_teams_active');
        $this->forge->createTable('hrd_teams');
    }

    private function createTeamUsersTable(): void
    {
        $this->forge->addField([
            'hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'assigned_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'assigned_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('user_id', true);
        $this->forge->addKey('hrd_team_id', false, false, 'idx_hrd_team_users_team');
        $this->forge->addForeignKey('hrd_team_id', 'hrd_teams', 'id', 'CASCADE', 'CASCADE', 'fk_hrd_team_users_team');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_hrd_team_users_user');
        $this->forge->addForeignKey('assigned_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_hrd_team_users_assigner');
        $this->forge->createTable('hrd_team_users');
    }

    private function addApplicationAssignmentColumns(): void
    {
        $this->forge->addColumn('applications', [
            'assigned_hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'application_status'],
            'assigned_by_user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'assigned_hrd_team_id'],
            'assigned_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'assigned_by_user_id'],
        ]);
        $this->db->query(
            'ALTER TABLE `applications` '
            . 'ADD INDEX `idx_applications_assigned_team` (`assigned_hrd_team_id`), '
            . 'ADD INDEX `idx_applications_assigned_user` (`assigned_by_user_id`), '
            . 'ADD CONSTRAINT `fk_applications_assigned_team` FOREIGN KEY (`assigned_hrd_team_id`) REFERENCES `hrd_teams` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE, '
            . 'ADD CONSTRAINT `fk_applications_assigned_user` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    private function createAssignmentHistoriesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'from_hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'to_hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 30],
            'notes' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'changed_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['application_id', 'created_at'], false, false, 'idx_assignment_history_application');
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE', 'fk_assignment_history_application');
        $this->forge->addForeignKey('from_hrd_team_id', 'hrd_teams', 'id', 'CASCADE', 'SET NULL', 'fk_assignment_history_from_team');
        $this->forge->addForeignKey('to_hrd_team_id', 'hrd_teams', 'id', 'CASCADE', 'SET NULL', 'fk_assignment_history_to_team');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_assignment_history_user');
        $this->forge->createTable('application_assignment_histories');
    }

    private function seedTeams(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('hrd_teams')->insertBatch([
            ['code' => 'divisi-1', 'name' => 'Divisi 1', 'description' => 'Tim HRD Divisi 1.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'divisi-2', 'name' => 'Divisi 2', 'description' => 'Tim HRD Divisi 2.', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['name' => 'Melihat tim HRD', 'code' => 'hrd.teams.view', 'module' => 'hrd_teams', 'description' => 'Melihat divisi HRD dan anggota setiap divisi.'],
            ['name' => 'Mengelola tim HRD', 'code' => 'hrd.teams.manage', 'module' => 'hrd_teams', 'description' => 'Membuat dan mengubah divisi serta menentukan anggota tim.'],
            ['name' => 'Melihat list pelamar', 'code' => 'applicants.pool.view', 'module' => 'applicants', 'description' => 'Melihat pelamar yang belum dipilih oleh divisi HRD.'],
            ['name' => 'Memilih pelamar', 'code' => 'applicants.assign', 'module' => 'applicants', 'description' => 'Memilih pelamar untuk diproses oleh divisi HRD sendiri.'],
        ];
        foreach ($rows as $row) {
            $existing = $this->db->table('permissions')->where('code', $row['code'])->get()->getRowArray();
            if ($existing === null) {
                $this->db->table('permissions')->insert($row + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSION_CODES)->get()->getResultArray(), 'id', 'code');
        $mapping = [
            'SUPER_ADMIN' => self::PERMISSION_CODES,
            'HRD_MANAGER' => self::PERMISSION_CODES,
            'RECRUITER' => ['hrd.teams.view', 'applicants.pool.view', 'applicants.assign'],
            'VIEWER' => ['hrd.teams.view', 'applicants.pool.view'],
        ];
        foreach ($mapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                if (! isset($roles[$roleCode], $permissions[$permissionCode])) {
                    continue;
                }
                $exists = $this->db->table('role_permissions')->where('role_id', $roles[$roleCode])->where('permission_id', $permissions[$permissionCode])->countAllResults() > 0;
                if (! $exists) {
                    $this->db->table('role_permissions')->insert(['role_id' => $roles[$roleCode], 'permission_id' => $permissions[$permissionCode], 'assigned_by' => null, 'assigned_at' => $now]);
                }
            }
        }
    }
}
