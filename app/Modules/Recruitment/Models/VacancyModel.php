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
        'department',
        'location',
        'employment_type',
        'minimum_education',
        'minimum_age',
        'maximum_age',
        'status',
        'opened_at',
        'closed_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function findPublicOpen(DateTimeInterface $now, ?int $limit = null): array
    {
        $builder = $this->builder()
            ->where('status', 'open')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('opened_at', null)
                ->orWhere('opened_at <=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->groupStart()
                ->where('closed_at', null)
                ->orWhere('closed_at >=', $now->format('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('opened_at', 'DESC')
            ->orderBy('title', 'ASC');

        if ($limit !== null) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }
}
