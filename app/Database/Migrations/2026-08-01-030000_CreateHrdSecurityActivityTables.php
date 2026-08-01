<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHrdSecurityActivityTables extends Migration
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
            'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'session_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500],
            'device_label' => ['type' => 'VARCHAR', 'constraint' => 150],
            'last_activity_at' => ['type' => 'DATETIME'],
            'expires_at' => ['type' => 'DATETIME'],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('session_hash');
        $this->forge->addKey(['user_id', 'revoked_at', 'expires_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_sessions');

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'was_successful' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500],
            'device_label' => ['type' => 'VARCHAR', 'constraint' => 150],
            'occurred_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'occurred_at']);
        $this->forge->addKey(['email', 'occurred_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('user_login_history');
    }

    public function down(): void
    {
        $this->forge->dropTable('user_login_history', true);
        $this->forge->dropTable('user_sessions', true);
    }
}
