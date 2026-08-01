<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;
use DateTimeInterface;

class VacancyModel extends Model
{
    protected $table = 'vacancies';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code',
        'title',
        'summary',
        'job_description',
        'responsibilities',
        'qualifications',
        'department_id',
        'location',
        'employment_type',
        'minimum_education',
        'minimum_age',
        'maximum_age',
        'headcount',
        'salary_min',
        'salary_max',
        'show_salary',
        'status',
        'opened_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findPublicOpen(
        DateTimeInterface $now,
        ?int $limit = null,
        string $keyword = '',
        string $departmentCode = '',
    ): array
    {
        $builder = $this->builder()
            ->select(
                'vacancies.*, departments.code AS department_code, departments.name AS department, '
                . 'requirement_groups.code AS requirement_group_code, requirement_groups.name AS requirement_group_name, '
                . 'requirement_groups.max_positions',
            )
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('requirement_groups', 'requirement_groups.id = vacancies.requirement_group_id')
            ->where('vacancies.status', 'open')
            ->where('vacancies.deleted_at', null)
            ->where('departments.is_active', 1)
            ->where('requirement_groups.is_active', 1)
            ->groupStart()
                ->where('vacancies.opened_at', null)
                ->orWhere('vacancies.opened_at <=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->groupStart()
                ->where('vacancies.closed_at', null)
                ->orWhere('vacancies.closed_at >=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('vacancies.opened_at', 'DESC')
            ->orderBy('vacancies.title', 'ASC');

        if ($keyword !== '') {
            $builder
                ->groupStart()
                    ->like('vacancies.title', $keyword)
                    ->orLike('departments.name', $keyword)
                    ->orLike('vacancies.location', $keyword)
                    ->orLike('vacancies.employment_type', $keyword)
                    ->orLike('vacancies.minimum_education', $keyword)
                ->groupEnd();
        }

        if ($departmentCode !== '') {
            $builder->where('departments.code', $departmentCode);
        }

        if ($limit !== null) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }
}
