<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApplicationRejections extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'stage_code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'stage_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 150],
            'stage_order_snapshot' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
            'reason_template_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'reason_title_snapshot' => ['type' => 'VARCHAR', 'constraint' => 150],
            'reason_text_snapshot' => ['type' => 'TEXT'],
            'internal_notes' => ['type' => 'TEXT', 'null' => true],
            'rejected_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'rejected_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('application_id');
        $this->forge->addKey(['stage_code', 'rejected_at']);
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reason_template_id', 'rejection_reason_templates', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('rejected_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('application_rejections');

        $this->backfillLegacyRejections();
    }

    public function down(): void
    {
        $this->forge->dropTable('application_rejections', true);
    }

    private function backfillLegacyRejections(): void
    {
        $applications = $this->db->table('applications AS applications')
            ->select('applications.id, applications.public_message, applications.updated_at, vacancies.recruitment_process_template_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->where('applications.application_status', 'rejected')
            ->get()->getResultArray();

        foreach ($applications as $application) {
            $history = $this->db->table('application_status_histories')
                ->where('application_id', (int) $application['id'])
                ->where('new_status', 'rejected')
                ->orderBy('created_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()->getRowArray();
            $stageCode = $this->normalizeStageCode((string) ($history['previous_status'] ?? ''));
            $stage = $this->db->table('recruitment_process_template_stages AS links')
                ->select('links.display_order, stages.code, stages.name')
                ->join('recruitment_stages AS stages', 'stages.id = links.stage_id')
                ->where('links.template_id', (int) $application['recruitment_process_template_id'])
                ->where('stages.code', $stageCode)
                ->get()->getRowArray();
            if ($stage === null) {
                $stage = $this->db->table('recruitment_stages')
                    ->select('code, name, display_order')
                    ->where('code', $stageCode)
                    ->get()->getRowArray();
            }

            $rejectedAt = (string) ($history['created_at'] ?? $application['updated_at'] ?? date('Y-m-d H:i:s'));
            $publicMessage = trim((string) ($application['public_message'] ?? ''));
            $this->db->table('application_rejections')->insert([
                'application_id' => (int) $application['id'],
                'stage_code' => $stageCode !== '' ? $stageCode : 'unknown',
                'stage_name_snapshot' => (string) ($stage['name'] ?? ($stageCode !== '' ? ucwords(str_replace('_', ' ', $stageCode)) : 'Tahap tidak tercatat')),
                'stage_order_snapshot' => isset($stage['display_order']) ? (int) $stage['display_order'] : null,
                'reason_template_id' => null,
                'reason_title_snapshot' => 'Riwayat penolakan lama',
                'reason_text_snapshot' => $publicMessage !== '' ? $publicMessage : 'Alasan penolakan belum tercatat secara terstruktur.',
                'internal_notes' => trim((string) ($history['notes'] ?? '')) ?: null,
                'rejected_by' => ! empty($history['changed_by']) ? (int) $history['changed_by'] : null,
                'rejected_at' => $rejectedAt,
                'created_at' => $rejectedAt,
                'updated_at' => $rejectedAt,
            ]);
        }
    }

    private function normalizeStageCode(string $code): string
    {
        return match ($code) {
            'screening_passed', 'screening_failed' => 'document_screening',
            'reviewed' => 'under_review',
            'interview_hr', 'interview_scheduled' => 'hrd_interview',
            'interview_user' => 'user_interview',
            'hired' => 'accepted',
            default => $code,
        };
    }
}
