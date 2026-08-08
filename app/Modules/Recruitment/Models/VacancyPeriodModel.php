<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;

class VacancyPeriodModel extends Model
{
    protected $table = 'vacancy_recruitment_periods';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'vacancy_id',
        'period_name',
        'period_code',
        'opened_at',
        'closed_at',
        'headcount',
        'status',
        'notes',
        'is_initial',
        'created_by',
        'updated_by',
    ];
}
