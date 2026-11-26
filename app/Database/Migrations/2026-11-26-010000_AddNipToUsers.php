<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNipToUsers extends Migration
{
    public function up(): void
    {
        $table = $this->db->protectIdentifiers('users', true);
        $this->db->query("ALTER TABLE {$table} ADD COLUMN nip VARCHAR(32) NULL, ADD UNIQUE KEY uq_users_nip (nip)");
    }

    public function down(): void
    {
        $table = $this->db->protectIdentifiers('users', true);
        $this->db->query("ALTER TABLE {$table} DROP INDEX uq_users_nip, DROP COLUMN nip");
    }
}
