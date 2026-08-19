<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveUnusedApplicantAndApplicationColumns extends Migration
{
    public function up(): void
    {
        $this->db->query(
            'ALTER TABLE `applications` '
            . 'DROP FOREIGN KEY `fk_applications_assigned_team`, '
            . 'DROP FOREIGN KEY `fk_applications_assigned_user`, '
            . 'DROP INDEX `idx_applications_assigned_team`, '
            . 'DROP INDEX `idx_applications_assigned_user`'
        );

        $this->forge->dropColumn('applications', [
            'cv_path',
            'document_bundle_path',
            'portfolio_url',
            'assigned_hrd_team_id',
            'assigned_by_user_id',
            'assigned_at',
        ]);

        $this->db->query('ALTER TABLE `applicants` DROP INDEX `idx_applicants_verified`');
        $this->forge->dropColumn('applicants', [
            'password_hash',
            'email_verified_at',
            'failed_login_attempts',
            'locked_until',
            'last_login_at',
            'last_login_ip',
            'remember_token_hash',
            'remember_token_expires_at',
        ]);
    }

    public function down(): void
    {
        $this->forge->addColumn('applicants', [
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'training_experience'],
            'email_verified_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'password_hash'],
            'failed_login_attempts' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0, 'after' => 'email_verified_at'],
            'locked_until' => ['type' => 'DATETIME', 'null' => true, 'after' => 'failed_login_attempts'],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'locked_until'],
            'last_login_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true, 'after' => 'last_login_at'],
            'remember_token_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'after' => 'last_login_ip'],
            'remember_token_expires_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'remember_token_hash'],
        ]);
        $this->db->query('ALTER TABLE `applicants` ADD INDEX `idx_applicants_verified` (`email_verified_at`)');

        $this->forge->addColumn('applications', [
            'cv_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'preference_order'],
            'document_bundle_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'cv_path'],
            'portfolio_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'document_bundle_path'],
            'assigned_hrd_team_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'application_status'],
            'assigned_by_user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'assigned_hrd_team_id'],
            'assigned_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'assigned_by_user_id'],
        ]);
        $this->db->query(
            'ALTER TABLE `applications` '
            . 'ADD INDEX `idx_applications_assigned_team` (`assigned_hrd_team_id`), '
            . 'ADD INDEX `idx_applications_assigned_user` (`assigned_by_user_id`), '
            . 'ADD CONSTRAINT `fk_applications_assigned_team` FOREIGN KEY (`assigned_hrd_team_id`) REFERENCES `hrd_teams` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE, '
            . 'ADD CONSTRAINT `fk_applications_assigned_user` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }
}
