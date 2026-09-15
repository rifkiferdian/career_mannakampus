<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkExperienceLeavingReason extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('application_work_experiences', [
            'leaving_reason' => ['type' => 'TEXT', 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('application_work_experiences', 'leaving_reason');
    }
}
