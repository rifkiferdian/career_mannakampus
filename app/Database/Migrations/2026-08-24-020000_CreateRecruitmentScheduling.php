<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruitmentScheduling extends Migration
{
    private const PERMISSIONS = [
        'schedules.view',
        'schedules.manage',
        'schedules.attendance',
        'schedules.view_all',
    ];

    public function up(): void
    {
        $this->forge->addColumn('recruitment_stages', [
            'is_schedulable' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'sla_days',
            ],
        ]);

        $this->db->table('recruitment_stages')
            ->whereIn('code', ['written_test', 'written_test_1', 'written_test_2', 'hrd_interview', 'user_interview', 'psychotest', 'medical_checkup'])
            ->update(['is_schedulable' => 1]);

        $this->createSchedulesTable();
        $this->createHistoriesTable();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $permissionIds = array_column(
            $this->db->table('permissions')->select('id')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(),
            'id'
        );
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        $this->forge->dropTable('recruitment_schedule_histories', true);
        $this->forge->dropTable('recruitment_schedules', true);
        $this->forge->dropColumn('recruitment_stages', 'is_schedulable');
    }

    private function createSchedulesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'stage_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'scheduled_at' => ['type' => 'DATETIME'],
            'venue' => ['type' => 'VARCHAR', 'constraint' => 1000],
            'pic_user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'instructions' => ['type' => 'TEXT', 'null' => true],
            'confirmation_deadline_at' => ['type' => 'DATETIME'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'scheduled'],
            'candidate_note' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['application_id', 'status'], false, false, 'idx_schedule_application_status');
        $this->forge->addKey(['pic_user_id', 'scheduled_at'], false, false, 'idx_schedule_pic_time');
        $this->forge->addKey(['scheduled_at', 'status'], false, false, 'idx_schedule_time_status');
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE', 'fk_schedule_application');
        $this->forge->addForeignKey('stage_id', 'recruitment_stages', 'id', 'RESTRICT', 'CASCADE', 'fk_schedule_stage');
        $this->forge->addForeignKey('pic_user_id', 'users', 'id', 'RESTRICT', 'CASCADE', 'fk_schedule_pic');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'CASCADE', 'fk_schedule_creator');
        $this->forge->createTable('recruitment_schedules');
    }

    private function createHistoriesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'schedule_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 50],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'changed_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['schedule_id', 'created_at'], false, false, 'idx_schedule_history_time');
        $this->forge->addForeignKey('schedule_id', 'recruitment_schedules', 'id', 'CASCADE', 'CASCADE', 'fk_schedule_history_schedule');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_schedule_history_user');
        $this->forge->createTable('recruitment_schedule_histories');
    }

    private function seedPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['name' => 'Melihat jadwal seleksi', 'code' => 'schedules.view', 'module' => 'schedules', 'description' => 'Melihat jadwal seleksi dan wawancara tim sendiri.'],
            ['name' => 'Mengelola jadwal seleksi', 'code' => 'schedules.manage', 'module' => 'schedules', 'description' => 'Membuat, mengubah, dan membatalkan jadwal seleksi.'],
            ['name' => 'Mencatat kehadiran', 'code' => 'schedules.attendance', 'module' => 'schedules', 'description' => 'Mencatat kehadiran kandidat pada jadwal seleksi.'],
            ['name' => 'Melihat seluruh jadwal', 'code' => 'schedules.view_all', 'module' => 'schedules', 'description' => 'Melihat jadwal seleksi seluruh tim HRD.'],
        ];
        foreach ($rows as $row) {
            $this->db->table('permissions')->insert($row + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id', 'code');
        $mapping = [
            'SUPER_ADMIN' => self::PERMISSIONS,
            'HRD_MANAGER' => self::PERMISSIONS,
            'RECRUITER' => ['schedules.view', 'schedules.manage', 'schedules.attendance'],
            'VIEWER' => ['schedules.view'],
        ];
        foreach ($mapping as $roleCode => $codes) {
            foreach ($codes as $code) {
                if (isset($roles[$roleCode], $permissions[$code])) {
                    $this->db->table('role_permissions')->insert([
                        'role_id' => $roles[$roleCode],
                        'permission_id' => $permissions[$code],
                        'assigned_by' => null,
                        'assigned_at' => $now,
                    ]);
                }
            }
        }
    }
}
