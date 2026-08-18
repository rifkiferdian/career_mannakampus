<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;

class ApplicantModel extends Model
{
    protected $table = 'applicants';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'uuid',
        'nik_hash',
        'nik_encrypted',
        'full_name',
        'email',
        'phone',
        'profile_photo_path',
        'birth_place',
        'birth_date',
        'height_cm',
        'gender',
        'marital_status',
        'religion',
        'address',
        'last_education',
        'institution',
        'major',
        'gpa',
        'training_experience',
        'privacy_consent_at',
        'privacy_policy_version',
        'registration_ip',
        'registration_user_agent',
        'is_active',
        'assigned_hrd_team_id',
        'assigned_by_user_id',
        'assigned_at',
    ];
}
