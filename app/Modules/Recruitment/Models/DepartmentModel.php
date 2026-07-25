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
            ->where('departments.is_active', 1)
            ->where('vacancies.status', 'open')
            ->where('vacancies.deleted_at', null)
            ->groupStart()
                ->where('vacancies.opened_at', null)
                ->orWhere('vacancies.opened_at <=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->groupStart()
                ->where('vacancies.closed_at', null)
                ->orWhere('vacancies.closed_at >=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('departments.display_order', 'ASC')
            ->orderBy('departments.name', 'ASC')
            ->get()
            ->getResultArray();
    }
}
