<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDefaultHrdTeam extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('hrd_teams', [
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'is_active',
            ],
        ]);
        $this->db->query('ALTER TABLE `hrd_teams` ADD INDEX `idx_hrd_teams_default` (`is_default`)');

        $defaultTeam = $this->db->table('hrd_teams')
            ->select('id')
            ->where('is_active', 1)
            ->orderBy('id')
            ->get()->getRowArray();
        if ($defaultTeam !== null) {
            $this->db->table('hrd_teams')->where('id', (int) $defaultTeam['id'])->update(['is_default' => 1]);
        }
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `hrd_teams` DROP INDEX `idx_hrd_teams_default`');
        $this->forge->dropColumn('hrd_teams', 'is_default');
    }
}
