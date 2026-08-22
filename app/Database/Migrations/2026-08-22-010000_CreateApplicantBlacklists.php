<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApplicantBlacklists extends Migration
{
    private const PERMISSION_CODES = [
        'applicants.blacklist.view',
        'applicants.blacklist.manage',
    ];

    public function up(): void
    {
        $this->createBlacklistsTable();
        $this->createHistoriesTable();
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

        $this->forge->dropTable('applicant_blacklist_histories', true);
        $this->forge->dropTable('applicant_blacklists', true);
    }

    private function createBlacklistsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'applicant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 1000],
            'internal_notes' => ['type' => 'TEXT', 'null' => true],
            'starts_at' => ['type' => 'DATETIME'],
            'ends_at' => ['type' => 'DATETIME', 'null' => true],
            'is_permanent' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'revocation_reason' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('applicant_id', 'uq_applicant_blacklist_applicant');
        $this->forge->addKey(['revoked_at', 'is_permanent', 'starts_at', 'ends_at'], false, false, 'idx_applicant_blacklist_period');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'CASCADE', 'fk_applicant_blacklist_applicant');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_applicant_blacklist_creator');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_applicant_blacklist_updater');
        $this->forge->addForeignKey('revoked_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_applicant_blacklist_revoker');
        $this->forge->createTable('applicant_blacklists');
    }

    private function createHistoriesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'blacklist_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 30],
            'reason_snapshot' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'notes_snapshot' => ['type' => 'TEXT', 'null' => true],
            'starts_at_snapshot' => ['type' => 'DATETIME', 'null' => true],
            'ends_at_snapshot' => ['type' => 'DATETIME', 'null' => true],
            'is_permanent_snapshot' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'action_notes' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'changed_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['blacklist_id', 'created_at'], false, false, 'idx_blacklist_history');
        $this->forge->addKey(['action', 'created_at'], false, false, 'idx_blacklist_history_action');
        $this->forge->addForeignKey('blacklist_id', 'applicant_blacklists', 'id', 'CASCADE', 'CASCADE', 'fk_blacklist_history_blacklist');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_blacklist_history_user');
        $this->forge->createTable('applicant_blacklist_histories');
    }

    private function seedPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $permissions = [
            ['name' => 'Melihat blacklist pelamar', 'code' => 'applicants.blacklist.view', 'module' => 'applicants', 'description' => 'Melihat pelamar yang diblokir dari seluruh pendaftaran lowongan.'],
            ['name' => 'Mengelola blacklist pelamar', 'code' => 'applicants.blacklist.manage', 'module' => 'applicants', 'description' => 'Menambahkan, memperbarui, mengaktifkan kembali, dan mencabut blacklist pelamar.'],
        ];
        foreach ($permissions as $permission) {
            if ($this->db->table('permissions')->where('code', $permission['code'])->countAllResults() === 0) {
                $this->db->table('permissions')->insert($permission + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $roleIds = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissionIds = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSION_CODES)->get()->getResultArray(), 'id', 'code');
        $mapping = [
            'SUPER_ADMIN' => self::PERMISSION_CODES,
            'HRD_MANAGER' => self::PERMISSION_CODES,
            'RECRUITER' => ['applicants.blacklist.view'],
        ];
        foreach ($mapping as $roleCode => $codes) {
            foreach ($codes as $code) {
                if (! isset($roleIds[$roleCode], $permissionIds[$code])) {
                    continue;
                }
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roleIds[$roleCode])
                    ->where('permission_id', $permissionIds[$code])
                    ->countAllResults() > 0;
                if (! $exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id' => $roleIds[$roleCode],
                        'permission_id' => $permissionIds[$code],
                        'assigned_by' => null,
                        'assigned_at' => $now,
                    ]);
                }
            }
        }
    }
}
