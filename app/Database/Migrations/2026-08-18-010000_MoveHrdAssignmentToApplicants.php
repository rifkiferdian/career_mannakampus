<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MoveHrdAssignmentToApplicants extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('applicants', [
            'assigned_hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'is_active'],
            'assigned_by_user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'assigned_hrd_team_id'],
            'assigned_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'assigned_by_user_id'],
        ]);
        $this->db->query(
            'ALTER TABLE `applicants` '
            . 'ADD INDEX `idx_applicants_assigned_team` (`assigned_hrd_team_id`), '
            . 'ADD INDEX `idx_applicants_assigned_user` (`assigned_by_user_id`), '
            . 'ADD CONSTRAINT `fk_applicants_assigned_team` FOREIGN KEY (`assigned_hrd_team_id`) REFERENCES `hrd_teams` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE, '
            . 'ADD CONSTRAINT `fk_applicants_assigned_user` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE'
        );

        $this->createHistoryTable();
        $this->backfillApplicantAssignments();
    }

    public function down(): void
    {
        $this->db->query(
            'UPDATE `applications` AS `applications` '
            . 'INNER JOIN `applicants` AS `applicants` ON `applicants`.`id` = `applications`.`applicant_id` '
            . 'SET `applications`.`assigned_hrd_team_id` = `applicants`.`assigned_hrd_team_id`, '
            . '`applications`.`assigned_by_user_id` = `applicants`.`assigned_by_user_id`, '
            . '`applications`.`assigned_at` = `applicants`.`assigned_at` '
            . 'WHERE `applicants`.`assigned_hrd_team_id` IS NOT NULL'
        );
        $this->forge->dropTable('applicant_assignment_histories', true);
        $this->db->query('ALTER TABLE `applicants` DROP FOREIGN KEY `fk_applicants_assigned_team`');
        $this->db->query('ALTER TABLE `applicants` DROP FOREIGN KEY `fk_applicants_assigned_user`');
        $this->db->query('ALTER TABLE `applicants` DROP INDEX `idx_applicants_assigned_team`');
        $this->db->query('ALTER TABLE `applicants` DROP INDEX `idx_applicants_assigned_user`');
        $this->forge->dropColumn('applicants', ['assigned_hrd_team_id', 'assigned_by_user_id', 'assigned_at']);
    }

    private function createHistoryTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'applicant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'from_hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'to_hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 30],
            'notes' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'changed_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['applicant_id', 'created_at'], false, false, 'idx_applicant_assignment_history');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'CASCADE', 'fk_applicant_assignment_history_applicant');
        $this->forge->addForeignKey('from_hrd_team_id', 'hrd_teams', 'id', 'CASCADE', 'SET NULL', 'fk_applicant_assignment_history_from_team');
        $this->forge->addForeignKey('to_hrd_team_id', 'hrd_teams', 'id', 'CASCADE', 'SET NULL', 'fk_applicant_assignment_history_to_team');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_applicant_assignment_history_user');
        $this->forge->createTable('applicant_assignment_histories');
    }

    private function backfillApplicantAssignments(): void
    {
        $this->db->query(
            'UPDATE `applicants` AS `applicants` '
            . 'INNER JOIN `applications` AS `chosen` ON `chosen`.`applicant_id` = `applicants`.`id` '
            . 'AND `chosen`.`assigned_hrd_team_id` IS NOT NULL '
            . 'SET `applicants`.`assigned_hrd_team_id` = `chosen`.`assigned_hrd_team_id`, '
            . '`applicants`.`assigned_by_user_id` = `chosen`.`assigned_by_user_id`, '
            . '`applicants`.`assigned_at` = `chosen`.`assigned_at` '
            . 'WHERE NOT EXISTS ('
            . 'SELECT 1 FROM `applications` AS `earlier` '
            . 'WHERE `earlier`.`applicant_id` = `chosen`.`applicant_id` '
            . 'AND `earlier`.`assigned_hrd_team_id` IS NOT NULL '
            . 'AND (`earlier`.`assigned_at` < `chosen`.`assigned_at` '
            . 'OR (`earlier`.`assigned_at` = `chosen`.`assigned_at` AND `earlier`.`id` < `chosen`.`id`))'
            . ')'
        );

        $this->db->query(
            'UPDATE `applications` AS `applications` '
            . 'INNER JOIN `applicants` AS `applicants` ON `applicants`.`id` = `applications`.`applicant_id` '
            . 'SET `applications`.`assigned_hrd_team_id` = `applicants`.`assigned_hrd_team_id`, '
            . '`applications`.`assigned_by_user_id` = `applicants`.`assigned_by_user_id`, '
            . '`applications`.`assigned_at` = `applicants`.`assigned_at` '
            . 'WHERE `applicants`.`assigned_hrd_team_id` IS NOT NULL'
        );

        $this->db->query(
            'INSERT INTO `applicant_assignment_histories` '
            . '(`applicant_id`, `from_hrd_team_id`, `to_hrd_team_id`, `action`, `notes`, `changed_by`, `created_at`) '
            . 'SELECT `applications`.`applicant_id`, `history`.`from_hrd_team_id`, `history`.`to_hrd_team_id`, '
            . '`history`.`action`, `history`.`notes`, `history`.`changed_by`, `history`.`created_at` '
            . 'FROM `application_assignment_histories` AS `history` '
            . 'INNER JOIN `applications` AS `applications` ON `applications`.`id` = `history`.`application_id`'
        );
    }
}
