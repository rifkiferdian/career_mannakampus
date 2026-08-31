<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandApplicantGradeScale extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('applicants', [
            'gpa' => ['name' => 'gpa', 'type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->db->table('applicants')->where('gpa >', 9.99)->update(['gpa' => null]);
        $this->forge->modifyColumn('applicants', [
            'gpa' => ['name' => 'gpa', 'type' => 'DECIMAL', 'constraint' => '3,2', 'null' => true],
        ]);
    }
}
