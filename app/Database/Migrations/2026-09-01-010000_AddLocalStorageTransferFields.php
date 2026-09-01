<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLocalStorageTransferFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('applicant_documents', [
            'sha256_checksum' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'file_size',
            ],
            'local_transfer_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
                'after' => 'sha256_checksum',
            ],
            'local_transferred_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'local_transfer_status',
            ],
            'local_confirmed_checksum' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'local_transferred_at',
            ],
            'local_confirmed_size' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'after' => 'local_confirmed_checksum',
            ],
            'hosting_deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'local_confirmed_size',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `applicant_documents` '
            . 'ADD INDEX `applicant_documents_local_transfer_idx` '
            . '(`local_transfer_status`, `hosting_deleted_at`, `created_at`)',
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE `applicant_documents` '
            . 'DROP INDEX `applicant_documents_local_transfer_idx`',
        );
        $this->forge->dropColumn('applicant_documents', [
            'sha256_checksum',
            'local_transfer_status',
            'local_transferred_at',
            'local_confirmed_checksum',
            'local_confirmed_size',
            'hosting_deleted_at',
        ]);
    }
}
