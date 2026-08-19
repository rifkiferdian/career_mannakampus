<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetManualScreeningWorkflow extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE applications MODIFY application_status VARCHAR(50) NOT NULL DEFAULT 'lamaran_baru'");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE applications MODIFY application_status VARCHAR(50) NOT NULL DEFAULT 'submitted'");
    }
}
