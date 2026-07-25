<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicationPreferenceOrder extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('applications', [
            'preference_order' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 1,
                'after'      => 'vacancy_id',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `applications` '
            . 'ADD UNIQUE INDEX `uq_applications_batch_preference` (`batch_id`, `preference_order`)',
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE `applications` DROP INDEX `uq_applications_batch_preference`',
        );
        $this->forge->dropColumn('applications', 'preference_order');
    }
}
