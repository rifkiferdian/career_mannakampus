<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicantDomicileRegions extends Migration
{
    public function up(): void
    {
        $columns = [];
        foreach (['province_code', 'regency_code', 'district_code', 'village_code'] as $field) {
            $columns[$field] = ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true];
        }
        $this->forge->addColumn('applicants', $columns);
    }

    public function down(): void
    {
        $this->forge->dropColumn('applicants', ['province_code', 'regency_code', 'district_code', 'village_code']);
    }
}
