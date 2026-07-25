<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnforceSnapshotVersion extends Migration
{
    public function up(): void
    {
        $this->db->query(
            'ALTER TABLE `application_batches` '
            . "MODIFY `snapshot_version` VARCHAR(20) NOT NULL DEFAULT '2026-07-v1'",
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE `application_batches` '
            . "MODIFY `snapshot_version` VARCHAR(20) NULL DEFAULT '2026-07-v1'",
        );
    }
}
