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
        'created_at',
    ];
}
