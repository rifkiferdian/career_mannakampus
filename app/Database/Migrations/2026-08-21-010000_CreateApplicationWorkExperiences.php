<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApplicationWorkExperiences extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'batch_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'company_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'start_year' => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true],
            'end_year' => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true, 'null' => true],
            'responsibilities' => ['type' => 'TEXT'],
            'display_order' => ['type' => 'TINYINT', 'constraint' => 2, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['batch_id', 'display_order']);
        $this->forge->addForeignKey('batch_id', 'application_batches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('application_work_experiences');
    }

    public function down(): void
    {
        $this->forge->dropTable('application_work_experiences', true);
    }
}
