<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class CreateMultiPositionApplicationStructure extends Migration
{
    public function up(): void
    {
        $this->createRequirementGroups();

        $this->forge->addColumn('vacancies', [
            'requirement_group_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'department_id',
            ],
        ]);

        $groupIds = array_column(
            $this->db->table('requirement_groups')->select('id, code')->get()->getResultArray(),
            'id',
            'code',
        );
        $vacancyGroups = [
            'ui-ux-designer'              => 'technology-professional',
            'programmer'                  => 'technology-professional',
            'content-marketing-specialist'=> 'marketing-professional',
            'people-operations-intern'    => 'people-entry',
            'pramuniaga'                  => 'retail-store',
        ];

        foreach ($vacancyGroups as $vacancyCode => $groupCode) {
            $this->db->table('vacancies')
                ->where('code', $vacancyCode)
                ->update(['requirement_group_id' => $groupIds[$groupCode]]);
        }

        if ($this->db->table('vacancies')->where('requirement_group_id', null)->countAllResults() > 0) {
            throw new RuntimeException('Ada lowongan yang belum memiliki kelompok persyaratan.');
        }

        $this->db->query(
            'ALTER TABLE `vacancies` MODIFY `requirement_group_id` BIGINT UNSIGNED NOT NULL',
        );
        $this->db->query(
            'ALTER TABLE `vacancies` ADD CONSTRAINT `fk_vacancies_requirement_group` '
            . 'FOREIGN KEY (`requirement_group_id`) REFERENCES `requirement_groups` (`id`) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT',
        );

        $this->createApplicationBatches();

        $this->forge->addColumn('applications', [
            'batch_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'tracking_token_hash',
            ],
        ]);
        $this->db->query('ALTER TABLE `applications` ADD INDEX `idx_applications_batch` (`batch_id`)');
        $this->db->query(
            'ALTER TABLE `applications` ADD CONSTRAINT `fk_applications_batch` '
            . 'FOREIGN KEY (`batch_id`) REFERENCES `application_batches` (`id`) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT',
        );

        $this->createApplicantDocuments();
    }

    public function down(): void
    {
        $this->forge->dropTable('applicant_documents', true);
        $this->db->query('ALTER TABLE `applications` DROP FOREIGN KEY `fk_applications_batch`');
        $this->forge->dropColumn('applications', 'batch_id');
        $this->forge->dropTable('application_batches', true);
        $this->db->query('ALTER TABLE `vacancies` DROP FOREIGN KEY `fk_vacancies_requirement_group`');
        $this->forge->dropColumn('vacancies', 'requirement_group_id');
        $this->forge->dropTable('requirement_groups', true);
    }

    private function createRequirementGroups(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'max_positions' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 3],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('requirement_groups');

        $now = date('Y-m-d H:i:s');
        $this->db->table('requirement_groups')->insertBatch([
            ['code' => 'technology-professional', 'name' => 'Technology Professional', 'description' => 'Posisi profesional bidang teknologi dan produk digital.', 'max_positions' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'marketing-professional', 'name' => 'Marketing Professional', 'description' => 'Posisi profesional bidang pemasaran dan komunikasi.', 'max_positions' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'people-entry', 'name' => 'People & Internship', 'description' => 'Posisi pemula dan internship pada fungsi people.', 'max_positions' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'retail-store', 'name' => 'Retail Store', 'description' => 'Posisi operasional dan pelayanan toko.', 'max_positions' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function createApplicationBatches(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'batch_number' => ['type' => 'VARCHAR', 'constraint' => 30],
            'applicant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'requirement_group_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'position_count' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true],
            'submitted_at' => ['type' => 'DATETIME'],
            'submitted_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'submitted_user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('batch_number');
        $this->forge->addKey(['applicant_id', 'submitted_at']);
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('requirement_group_id', 'requirement_groups', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('application_batches');
    }

    private function createApplicantDocuments(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'applicant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'batch_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'document_type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'file_path' => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 100],
            'file_size' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['batch_id', 'document_type']);
        $this->forge->addKey('applicant_id');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('batch_id', 'application_batches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('applicant_documents');
    }
}
