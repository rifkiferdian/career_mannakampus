<?php

namespace App\Modules\Recruitment\Models;

use CodeIgniter\Model;

class ScreeningAnswerModel extends Model
{
    protected $table = 'application_screening_answers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'application_id',
        'question_id',
        'answer_value',
        'is_eligible',
        'score',
    ];
}
