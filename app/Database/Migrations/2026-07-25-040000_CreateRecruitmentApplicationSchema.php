<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruitmentApplicationSchema extends Migration
{
    public function up(): void
    {
        // Keep these fields nullable while legacy account records still exist.
        $this->forge->addColumn('applicants', [
            'nik_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'uuid',
            ],
            'nik_encrypted' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'nik_hash',
            ],
            'birth_place' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'phone',
            ],
            'birth_date' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'birth_place',
            ],
            'gender' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'birth_date',
            ],
            'address' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'gender',
            ],
            'last_education' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'address',
            ],
            'institution' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'last_education',
            ],
            'major' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'institution',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `applicants` ADD UNIQUE KEY `applicants_nik_hash_unique` (`nik_hash`)'
        );

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'employment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'minimum_education' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'minimum_age' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'null'       => true,
            ],
            'maximum_age' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'draft',
            ],
            'opened_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'closed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('status');
        $this->forge->addKey('opened_at');
        $this->forge->addKey('closed_at');
        $this->forge->createTable('vacancies');

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'application_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'tracking_token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
            ],
            'applicant_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'vacancy_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'cv_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'portfolio_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'work_experience' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'screening_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            'screening_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'screening_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'public_message' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'application_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'submitted',
            ],
            'submitted_at' => [
                'type' => 'DATETIME',
            ],
            'submitted_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'submitted_user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'reviewed_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('application_number');
        $this->forge->addUniqueKey('tracking_token_hash');
        $this->forge->addUniqueKey(['applicant_id', 'vacancy_id']);
        $this->forge->addKey('screening_status');
        $this->forge->addKey('application_status');
        $this->forge->addKey('submitted_at');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('vacancy_id', 'vacancies', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('applications');

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'application_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'status_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'application',
            ],
            'previous_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'new_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'changed_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('application_id');
        $this->forge->addKey(['status_type', 'created_at']);
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('application_status_histories');
    }

    public function down(): void
    {
        $this->forge->dropTable('application_status_histories', true);
        $this->forge->dropTable('applications', true);
        $this->forge->dropTable('vacancies', true);

        $this->db->query(
            'ALTER TABLE `applicants` DROP INDEX `applicants_nik_hash_unique`'
        );

        $this->forge->dropColumn('applicants', [
            'nik_hash',
            'nik_encrypted',
            'birth_place',
            'birth_date',
            'gender',
            'address',
            'last_education',
            'institution',
            'major',
        ]);
    }
}
