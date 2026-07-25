<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicationWizardFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('applicants', [
            'profile_photo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'phone',
            ],
        ]);

        $this->forge->addColumn('applications', [
            'skills' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'work_experience',
            ],
        ]);

        $this->forge->modifyColumn('applicants', [
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);
    }

    public function down(): void
    {
        $this->db->table('applicants')
            ->where('password_hash', null)
            ->update(['password_hash' => '']);

        $this->forge->modifyColumn('applicants', [
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
        ]);
        $this->forge->dropColumn('applications', 'skills');
        $this->forge->dropColumn('applicants', 'profile_photo_path');
    }
}
