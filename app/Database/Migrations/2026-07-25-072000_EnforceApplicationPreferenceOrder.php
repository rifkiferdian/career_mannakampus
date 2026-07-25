<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnforceApplicationPreferenceOrder extends Migration
{
    public function up(): void
    {
        $this->db->query(
            'ALTER TABLE `applications` '
            . 'MODIFY `preference_order` TINYINT UNSIGNED NOT NULL DEFAULT 1',
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE `applications` '
            . 'MODIFY `preference_order` TINYINT UNSIGNED NULL DEFAULT 1',
        );
    }
}
