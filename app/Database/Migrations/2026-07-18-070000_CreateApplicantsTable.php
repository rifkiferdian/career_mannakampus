<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApplicantsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'email_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'failed_login_attempts' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'locked_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_login_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'remember_token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'remember_token_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'privacy_consent_at' => [
                'type' => 'DATETIME',
            ],
            'privacy_policy_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'registration_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'registration_user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('phone');
        $this->forge->addKey('email_verified_at');
        $this->forge->addKey('is_active');
        $this->forge->addKey('created_at');
        $this->forge->addKey('deleted_at');
        $this->forge->createTable('applicants', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('applicants', true);
    }
}
