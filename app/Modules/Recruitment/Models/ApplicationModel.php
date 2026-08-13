<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;

class ApplicationModel extends Model
{
    protected $table = 'applications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'uuid',
        'application_number',
        'tracking_token_hash',
        'batch_id',
        'applicant_id',
        'vacancy_id',
        'vacancy_period_id',
        'preference_order',
        'cv_path',
        'document_bundle_path',
        'portfolio_url',
        'work_experience',
        'skills',
        'work_motivation',
        'career_goal',
        'screening_status',
        'screening_score',
        'screening_notes',
        'public_message',
        'application_status',
        'assigned_hrd_team_id',
        'assigned_by_user_id',
        'assigned_at',
        'submitted_at',
        'submitted_ip',
        'submitted_user_agent',
    ];
}
