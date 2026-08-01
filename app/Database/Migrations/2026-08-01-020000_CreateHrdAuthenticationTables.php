<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHrdAuthenticationTables extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('roles')) {
            $this->createRolesTable();
        }

        if (! $this->db->tableExists('users')) {
            $this->createUsersTable();
        }

        if (! $this->db->tableExists('user_roles')) {
            $this->createUserRolesTable();
        }
    }

    public function down(): void
    {
        // These tables can predate this migration and may be shared by other admin modules.
        // They are intentionally preserved to prevent destructive rollback of existing users.
    }

    private function createRolesTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_system' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('is_active');
        $this->forge->createTable('roles');
    }

    private function createUsersTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'email_verified_at' => ['type' => 'DATETIME', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'last_login_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'failed_login_attempts' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'locked_until' => ['type' => 'DATETIME', 'null' => true],
            'remember_token_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'remember_token_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('is_active');
        $this->forge->addKey('deleted_at');
        $this->forge->createTable('users');
    }

    private function createUserRolesTable(): void
    {
        $this->forge->addField([
            'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'role_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'assigned_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'assigned_at' => ['type' => 'DATETIME'],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['user_id', 'role_id'], true);
        $this->forge->addKey('role_id');
        $this->forge->addKey('assigned_by');
        $this->forge->addKey('expires_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('user_roles');
    }
}
