<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;
use DateTimeInterface;

class DepartmentModel extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code',
        'name',
        'description',
        'display_order',
        'is_active',
    ];

    /**
     * @return list<array{code: string, name: string}>
     */
    public function findWithOpenVacancies(DateTimeInterface $now): array
    {
        return $this->builder()
            ->distinct()
            ->select('departments.code, departments.name')
            ->join('vacancies', 'vacancies.department_id = departments.id')
            ->join('vacancy_recruitment_periods AS periods', 'periods.vacancy_id = vacancies.id')
            ->where('departments.is_active', 1)
            ->whereIn('periods.status', ['open', 'scheduled'])
            ->where('periods.deleted_at', null)
            ->where('vacancies.deleted_at', null)
            ->where('vacancies.status !=', 'archived')
            ->groupStart()
                ->where('periods.opened_at', null)
                ->orWhere('periods.opened_at <=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->groupStart()
                ->where('periods.closed_at', null)
                ->orWhere('periods.closed_at >=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('departments.display_order', 'ASC')
            ->orderBy('departments.name', 'ASC')
            ->get()
            ->getResultArray();
    }
}
