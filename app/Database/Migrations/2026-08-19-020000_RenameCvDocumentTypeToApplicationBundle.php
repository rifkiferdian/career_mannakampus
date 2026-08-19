<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameCvDocumentTypeToApplicationBundle extends Migration
{
    public function up(): void
    {
        $this->db->table('applicant_documents')
            ->where('document_type', 'cv')
            ->update(['document_type' => 'application_bundle']);
    }

    public function down(): void
    {
        $this->db->table('applicant_documents')
            ->where('document_type', 'application_bundle')
            ->update(['document_type' => 'cv']);
    }
}
