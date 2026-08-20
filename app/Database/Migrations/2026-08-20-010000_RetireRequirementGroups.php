<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class RetireRequirementGroups extends Migration
{
    public function up(): void
    {
        $this->db->query(
            'ALTER TABLE `vacancies` MODIFY `requirement_group_id` BIGINT UNSIGNED NULL',
        );
        $this->db->query(
            'ALTER TABLE `application_batches` MODIFY `requirement_group_id` BIGINT UNSIGNED NULL',
        );
    }

    public function down(): void
    {
        $group = $this->db->table('requirement_groups')
            ->select('id')
            ->orderBy('is_active', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        if ($group === null) {
            throw new RuntimeException('Rollback membutuhkan minimal satu kelompok persyaratan.');
        }

        $groupId = (int) $group['id'];
        $this->db->table('vacancies')->where('requirement_group_id', null)->update([
            'requirement_group_id' => $groupId,
        ]);
        $this->db->table('application_batches')->where('requirement_group_id', null)->update([
            'requirement_group_id' => $groupId,
        ]);

        $this->db->query(
            'ALTER TABLE `application_batches` MODIFY `requirement_group_id` BIGINT UNSIGNED NOT NULL',
        );
        $this->db->query(
            'ALTER TABLE `vacancies` MODIFY `requirement_group_id` BIGINT UNSIGNED NOT NULL',
        );
    }
}
