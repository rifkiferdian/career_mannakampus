<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLegacyFormFieldsAndScreeningTables extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('applicants', [
            'height_cm' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'birth_date',
            ],
            'marital_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'gender',
            ],
            'religion' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'marital_status',
            ],
            'gpa' => [
                'type'       => 'DECIMAL',
                'constraint' => '3,2',
                'null'       => true,
                'after'      => 'major',
            ],
            'training_experience' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'gpa',
            ],
        ]);

        $this->forge->addColumn('applications', [
            'document_bundle_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'cv_path',
            ],
            'work_motivation' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'work_experience',
            ],
            'career_goal' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'work_motivation',
            ],
        ]);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'vacancy_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'question_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'question_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'answer_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'is_required' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'is_knockout' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'expected_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'comparison_operator' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'display_order' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['vacancy_id', 'question_code']);
        $this->forge->addKey(['vacancy_id', 'display_order']);
        $this->forge->addForeignKey('vacancy_id', 'vacancies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('vacancy_screening_questions');

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
            'question_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'answer_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_eligible' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
            ],
            'score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['application_id', 'question_id']);
        $this->forge->addKey('question_id');
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('question_id', 'vacancy_screening_questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('application_screening_answers');
    }

    public function down(): void
    {
        $this->forge->dropTable('application_screening_answers', true);
        $this->forge->dropTable('vacancy_screening_questions', true);

        $this->forge->dropColumn('applications', [
            'document_bundle_path',
            'work_motivation',
            'career_goal',
        ]);

        $this->forge->dropColumn('applicants', [
            'height_cm',
            'marital_status',
            'religion',
            'gpa',
            'training_experience',
        ]);
    }
}
