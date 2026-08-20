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
        'recruitment_process_template_id',
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
                'vacancies.*, periods.id AS vacancy_period_id, periods.period_name AS recruitment_period_name, '
                . 'periods.period_code AS recruitment_period_code, periods.headcount AS period_headcount, '
                . 'periods.opened_at AS period_opened_at, periods.closed_at AS period_closed_at, '
                . 'departments.code AS department_code, departments.name AS department',
            )
            ->join('departments', 'departments.id = vacancies.department_id')
            ->join('vacancy_recruitment_periods AS periods', 'periods.vacancy_id = vacancies.id')
            ->whereIn('periods.status', ['open', 'scheduled'])
            ->where('periods.deleted_at', null)
            ->where('vacancies.deleted_at', null)
            ->where('vacancies.status !=', 'archived')
            ->where('departments.is_active', 1)
            ->groupStart()
                ->where('periods.opened_at', null)
                ->orWhere('periods.opened_at <=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->groupStart()
                ->where('periods.closed_at', null)
                ->orWhere('periods.closed_at >=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('periods.opened_at', 'DESC')
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
