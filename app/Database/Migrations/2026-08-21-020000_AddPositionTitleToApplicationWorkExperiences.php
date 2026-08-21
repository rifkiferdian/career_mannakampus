<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPositionTitleToApplicationWorkExperiences extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('application_work_experiences', [
            'position_title' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
                'after' => 'company_name',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('application_work_experiences', 'position_title');
    }
}
