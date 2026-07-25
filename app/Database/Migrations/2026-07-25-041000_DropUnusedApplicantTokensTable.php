<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropUnusedApplicantTokensTable extends Migration
{
    public function up(): void
    {
        $this->forge->dropTable('applicant_tokens', true);
    }

    public function down(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'applicant_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'token_type' => [
                'type'       => 'ENUM',
                'constraint' => ['EMAIL_VERIFICATION', 'PASSWORD_RESET'],
            ],
            'token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'request_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token_hash', 'uq_applicant_tokens_hash');
        $this->forge->addKey(['applicant_id', 'token_type'], false, false, 'idx_applicant_tokens_applicant');
        $this->forge->addKey(['token_type', 'expires_at'], false, false, 'idx_applicant_tokens_expiry');
        $this->forge->addForeignKey(
            'applicant_id',
            'applicants',
            'id',
            'CASCADE',
            'CASCADE',
            'fk_applicant_tokens_applicant'
        );
        $this->forge->createTable('applicant_tokens');
    }
}
