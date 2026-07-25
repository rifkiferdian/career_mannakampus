<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;

class ApplicationBatchModel extends Model
{
    protected $table = 'application_batches';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'uuid',
        'batch_number',
        'applicant_id',
        'requirement_group_id',
        'position_count',
        'applicant_snapshot',
        'snapshot_version',
        'submitted_at',
        'submitted_ip',
        'submitted_user_agent',
    ];
}
