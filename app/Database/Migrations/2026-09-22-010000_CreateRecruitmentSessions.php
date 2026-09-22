<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruitmentSessions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'stage_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'starts_at' => ['type' => 'DATETIME'],
            'ends_at' => ['type' => 'DATETIME', 'null' => true],
            'venue' => ['type' => 'VARCHAR', 'constraint' => 1000],
            'pic_user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'capacity' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'instructions' => ['type' => 'TEXT', 'null' => true],
            // Supported lifecycle: draft, scheduled, completed, cancelled.
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'created_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['starts_at', 'status'], false, false, 'idx_session_time_status');
        $this->forge->addKey(['pic_user_id', 'starts_at'], false, false, 'idx_session_pic_time');
        $this->forge->addForeignKey('stage_id', 'recruitment_stages', 'id', 'CASCADE', 'RESTRICT', 'fk_session_stage');
        $this->forge->addForeignKey('pic_user_id', 'users', 'id', 'CASCADE', 'RESTRICT', 'fk_session_pic');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'RESTRICT', 'fk_session_creator');
        $this->forge->createTable('recruitment_sessions');

        // Existing individual schedules remain valid without a session.
        $this->forge->addColumn('recruitment_schedules', [
            'session_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
        ]);
        // One application may only be registered once in the same session.
        $this->forge->addUniqueKey(['session_id', 'application_id'], 'uq_schedule_session_application');
        $this->forge->addForeignKey('session_id', 'recruitment_sessions', 'id', 'CASCADE', 'RESTRICT', 'fk_schedule_session');
        $this->forge->processIndexes('recruitment_schedules');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('recruitment_schedules', 'fk_schedule_session');
        $this->forge->dropKey('recruitment_schedules', 'uq_schedule_session_application');
        $this->forge->dropColumn('recruitment_schedules', 'session_id');
        $this->forge->dropTable('recruitment_sessions');
    }
}
