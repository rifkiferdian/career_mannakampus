<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveApplicationSkills extends Migration
{
    public function up(): void
    {
        if ($this->db->fieldExists('skills', 'applications')) {
            $this->forge->dropColumn('applications', 'skills');
        }
    }

    public function down(): void
    {
        if (! $this->db->fieldExists('skills', 'applications')) {
            $this->forge->addColumn('applications', [
                'skills' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'work_experience',
                ],
            ]);
        }
    }
}
