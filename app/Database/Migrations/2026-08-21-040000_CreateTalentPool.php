<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTalentPool extends Migration
{
    public function up(): void
    {
        $this->createCandidatesTable();
        $this->createHistoriesTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('talent_pool_histories', true);
        $this->forge->dropTable('talent_pool_candidates', true);
    }

    private function createCandidatesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'applicant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'target_department_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'recommended_position' => ['type' => 'VARCHAR', 'constraint' => 150],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'pool_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'available'],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 1000],
            'strength_notes' => ['type' => 'TEXT', 'null' => true],
            'internal_notes' => ['type' => 'TEXT', 'null' => true],
            'available_from' => ['type' => 'DATE', 'null' => true],
            'follow_up_at' => ['type' => 'DATE', 'null' => true],
            'last_contacted_at' => ['type' => 'DATETIME', 'null' => true],
            'saved_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'saved_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('application_id', 'uq_talent_pool_application');
        $this->forge->addUniqueKey('applicant_id', 'uq_talent_pool_applicant');
        $this->forge->addKey(['hrd_team_id', 'pool_status', 'follow_up_at'], false, false, 'idx_talent_pool_team_status_followup');
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE', 'fk_talent_pool_application');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'CASCADE', 'fk_talent_pool_applicant');
        $this->forge->addForeignKey('hrd_team_id', 'hrd_teams', 'id', 'CASCADE', 'SET NULL', 'fk_talent_pool_team');
        $this->forge->addForeignKey('target_department_id', 'departments', 'id', 'CASCADE', 'SET NULL', 'fk_talent_pool_department');
        $this->forge->addForeignKey('saved_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_talent_pool_saved_by');
        $this->forge->createTable('talent_pool_candidates');
    }

    private function createHistoriesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'talent_pool_candidate_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'action_code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'previous_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'new_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'related_vacancy_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'related_application_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'changed_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['talent_pool_candidate_id', 'created_at'], false, false, 'idx_talent_pool_history_candidate');
        $this->forge->addForeignKey('talent_pool_candidate_id', 'talent_pool_candidates', 'id', 'CASCADE', 'CASCADE', 'fk_talent_pool_history_candidate');
        $this->forge->addForeignKey('related_vacancy_id', 'vacancies', 'id', 'CASCADE', 'SET NULL', 'fk_talent_pool_history_vacancy');
        $this->forge->addForeignKey('related_application_id', 'applications', 'id', 'CASCADE', 'SET NULL', 'fk_talent_pool_history_application');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_talent_pool_history_user');
        $this->forge->createTable('talent_pool_histories');
    }
}
