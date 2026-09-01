<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;

class ApplicantDocumentModel extends Model
{
    protected $table = 'applicant_documents';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'applicant_id',
        'batch_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'sha256_checksum',
        'local_transfer_status',
        'local_transferred_at',
        'local_confirmed_checksum',
        'local_confirmed_size',
        'hosting_deleted_at',
        'created_at',
    ];
}
