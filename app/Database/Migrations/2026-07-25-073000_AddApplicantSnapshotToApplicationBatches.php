<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicantSnapshotToApplicationBatches extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('application_batches', [
            'applicant_snapshot' => [
                'type'  => 'JSON',
                'null'  => true,
                'after' => 'position_count',
            ],
            'snapshot_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => '2026-07-v1',
                'after'      => 'applicant_snapshot',
            ],
        ]);

        // Existing batches cannot be reconstructed perfectly, so capture the
        // current applicant profile as a clearly versioned legacy snapshot.
        $this->db->query(
            'UPDATE `application_batches` AS `b` '
            . 'INNER JOIN `applicants` AS `a` ON `a`.`id` = `b`.`applicant_id` '
            . 'SET `b`.`applicant_snapshot` = JSON_OBJECT('
            . "'snapshot_version', 'legacy-backfill-v1', "
            . "'captured_at', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%sP'), "
            . "'identity', JSON_OBJECT("
            . "'full_name', `a`.`full_name`, "
            . "'gender', `a`.`gender`, "
            . "'birth_place', `a`.`birth_place`, "
            . "'birth_date', `a`.`birth_date`, "
            . "'height_cm', `a`.`height_cm`, "
            . "'marital_status', `a`.`marital_status`, "
            . "'religion', `a`.`religion`), "
            . "'contact', JSON_OBJECT('email', `a`.`email`, 'phone', `a`.`phone`), "
            . "'address', `a`.`address`, "
            . "'education', JSON_OBJECT("
            . "'level', `a`.`last_education`, "
            . "'institution', `a`.`institution`, "
            . "'major', `a`.`major`, "
            . "'gpa', `a`.`gpa`, "
            . "'training_experience', `a`.`training_experience`), "
            . "'profile_photo_path', `a`.`profile_photo_path`"
            . ')',
        );

        $this->db->query(
            'ALTER TABLE `application_batches` '
            . 'MODIFY `applicant_snapshot` JSON NOT NULL',
        );
    }

    public function down(): void
    {
        $this->forge->dropColumn('application_batches', [
            'applicant_snapshot',
            'snapshot_version',
        ]);
    }
}
