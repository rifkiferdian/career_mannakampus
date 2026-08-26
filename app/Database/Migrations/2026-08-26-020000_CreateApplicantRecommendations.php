<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApplicantRecommendations extends Migration
{
    private const PERMISSIONS = ['recommendations.view', 'recommendations.manage'];

    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'applicant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'recommendation' => ['type' => 'VARCHAR', 'constraint' => 20],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'updated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('applicant_id', 'uq_applicant_recommendation');
        $this->forge->addForeignKey('applicant_id', 'applicants', 'id', 'CASCADE', 'CASCADE', 'fk_recommendation_applicant');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'CASCADE', 'RESTRICT', 'fk_recommendation_updated_by');
        $this->forge->createTable('applicant_recommendations');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'recommendation_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'aspect_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'answer_value' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['recommendation_id', 'aspect_id'], 'uq_recommendation_aspect_answer');
        $this->forge->addKey('aspect_id', false, false, 'idx_recommendation_answer_aspect');
        $this->forge->addForeignKey('recommendation_id', 'applicant_recommendations', 'id', 'CASCADE', 'CASCADE', 'fk_recommendation_answer_parent');
        $this->forge->addForeignKey('aspect_id', 'recommendation_aspects', 'id', 'CASCADE', 'RESTRICT', 'fk_recommendation_answer_aspect');
        $this->forge->createTable('applicant_recommendation_answers');

        $now = date('Y-m-d H:i:s');
        $rows = [
            ['name' => 'Melihat penilaian pelamar', 'code' => self::PERMISSIONS[0], 'module' => 'recommendations', 'description' => 'Melihat scorecard dan rekomendasi pada biodata pelamar.'],
            ['name' => 'Mengisi penilaian pelamar', 'code' => self::PERMISSIONS[1], 'module' => 'recommendations', 'description' => 'Mengisi dan memperbarui scorecard serta rekomendasi pelamar.'],
        ];
        foreach ($rows as $row) {
            $this->db->table('permissions')->insert($row + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id', 'code');
        $mapping = ['SUPER_ADMIN' => self::PERMISSIONS, 'HRD_MANAGER' => self::PERMISSIONS, 'RECRUITER' => self::PERMISSIONS, 'VIEWER' => [self::PERMISSIONS[0]]];
        foreach ($mapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                if (isset($roles[$roleCode], $permissions[$permissionCode])) {
                    $this->db->table('role_permissions')->insert(['role_id' => $roles[$roleCode], 'permission_id' => $permissions[$permissionCode], 'assigned_by' => null, 'assigned_at' => $now]);
                }
            }
        }
    }

    public function down(): void
    {
        $permissionIds = array_column($this->db->table('permissions')->select('id')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id');
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }
        $this->forge->dropTable('applicant_recommendation_answers', true);
        $this->forge->dropTable('applicant_recommendations', true);
    }
}
