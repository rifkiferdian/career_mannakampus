<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;

class ScreeningQuestionModel extends Model
{
    protected $table = 'vacancy_screening_questions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'vacancy_id',
        'question_code',
        'question_text',
        'answer_type',
        'answer_options',
        'is_required',
        'is_knockout',
        'expected_value',
        'comparison_operator',
        'display_order',
        'is_active',
    ];

    /**
     * @param list<int> $vacancyIds
     *
     * @return list<array<string, mixed>>
     */
    public function findForVacancies(array $vacancyIds): array
    {
        if ($vacancyIds === []) {
            return [];
        }

        return $this->select(
            'id, vacancy_id, question_code, question_text, answer_type, answer_options, '
            . 'is_required, is_knockout, expected_value, comparison_operator, display_order, is_active',
        )
            ->whereIn('vacancy_id', $vacancyIds)
            ->where('is_active', 1)
            ->orderBy('display_order', 'ASC')
            ->findAll();
    }
}
