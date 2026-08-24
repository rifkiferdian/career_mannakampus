<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHistoricalBlacklists extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'nik_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'nik_last_four' => ['type' => 'CHAR', 'constraint' => 4, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 1000],
            'internal_notes' => ['type' => 'TEXT', 'null' => true],
            'source' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'starts_at' => ['type' => 'DATETIME'],
            'ends_at' => ['type' => 'DATETIME', 'null' => true],
            'is_permanent' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'revocation_reason' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'match_count' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'last_matched_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('nik_hash');
        $this->forge->addKey('email');
        $this->forge->addKey('phone');
        $this->forge->addKey(['revoked_at', 'is_permanent', 'starts_at', 'ends_at'], false, false, 'idx_historical_blacklist_period');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_historical_blacklist_creator');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_historical_blacklist_updater');
        $this->forge->addForeignKey('revoked_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_historical_blacklist_revoker');
        $this->forge->createTable('historical_blacklists');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'historical_blacklist_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 30],
            'action_notes' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'changed_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['historical_blacklist_id', 'created_at'], false, false, 'idx_historical_blacklist_history');
        $this->forge->addForeignKey('historical_blacklist_id', 'historical_blacklists', 'id', 'CASCADE', 'CASCADE', 'fk_historical_blacklist_history_entry');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'CASCADE', 'SET NULL', 'fk_historical_blacklist_history_user');
        $this->forge->createTable('historical_blacklist_histories');
    }

    public function down(): void
    {
        $this->forge->dropTable('historical_blacklist_histories', true);
        $this->forge->dropTable('historical_blacklists', true);
    }
}
