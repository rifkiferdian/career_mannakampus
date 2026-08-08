<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class RecruitmentReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $vacancies = [
            ['ui-ux-designer', 'UI UX Designer', 'information-technology', 'Full-time', 'D3/S1', 18, 35, 'open'],
            ['content-marketing-specialist', 'Content Marketing Specialist', 'marketing', 'Full-time', 'D3/S1', 18, 35, 'open'],
            ['people-operations-intern', 'People Operations Intern', 'human-capital', 'Internship', 'D3/S1', 18, 25, 'open'],
            ['programmer', 'Programmer', 'information-technology', 'Full-time', 'D3/S1', 18, 40, 'open'],
            ['pramuniaga', 'Pramuniaga', 'operation', 'Full-time', 'SMA/SMK', 18, 25, 'open'],
        ];

        $vacancyIds = [];
        $vacancyBuilder = $this->db->table('vacancies');
        $departmentIds = array_column(
            $this->db->table('departments')
                ->select('id, code')
                ->where('is_active', 1)
                ->get()
                ->getResultArray(),
            'id',
            'code',
        );

        foreach ($vacancies as $vacancy) {
            [$code, $title, $departmentCode, $employmentType, $minimumEducation, $minimumAge, $maximumAge, $status] = $vacancy;

            if (! isset($departmentIds[$departmentCode])) {
                throw new RuntimeException("Departemen {$departmentCode} belum tersedia.");
            }

            $existing = $vacancyBuilder->select('id')->where('code', $code)->get()->getRowArray();

            if ($existing === null) {
                $vacancyBuilder->insert([
                    'code'              => $code,
                    'title'             => $title,
                    'department_id'     => $departmentIds[$departmentCode],
                    'location'          => 'Yogyakarta',
                    'employment_type'   => $employmentType,
                    'minimum_education' => $minimumEducation,
                    'minimum_age'       => $minimumAge,
                    'maximum_age'       => $maximumAge,
                    'status'            => $status,
                    'opened_at'         => $status === 'open' ? $now : null,
                    'closed_at'         => null,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                    'deleted_at'        => null,
                ]);
                $vacancyIds[$code] = (int) $this->db->insertID();
            } else {
                $vacancyIds[$code] = (int) $existing['id'];
                $vacancyBuilder->where('id', $existing['id'])->update([
                    'title'             => $title,
                    'department_id'     => $departmentIds[$departmentCode],
                    'location'          => 'Yogyakarta',
                    'employment_type'   => $employmentType,
                    'minimum_education' => $minimumEducation,
                    'minimum_age'       => $minimumAge,
                    'maximum_age'       => $maximumAge,
                    'status'            => $status,
                    'closed_at'         => null,
                    'updated_at'        => $now,
                    'deleted_at'        => null,
                ]);
            }
        }

        $periodBuilder = $this->db->table('vacancy_recruitment_periods');
        foreach ($vacancyIds as $vacancyId) {
            $existingPeriod = $periodBuilder->select('id')->where('vacancy_id', $vacancyId)->where('deleted_at', null)->get()->getRowArray();
            if ($existingPeriod !== null) {
                continue;
            }
            $periodBuilder->insert([
                'vacancy_id' => $vacancyId,
                'period_name' => 'Periode Awal',
                'period_code' => 'awal-' . $vacancyId,
                'opened_at' => $now,
                'closed_at' => null,
                'headcount' => 1,
                'status' => 'open',
                'notes' => 'Dibuat oleh seeder referensi rekrutmen.',
                'is_initial' => 1,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        $genericQuestions = [
            ['willing_shift', 'Apakah Anda bersedia bekerja dengan sistem shift?', 'boolean', 1, 1, '1', 'equals', 900],
            ['willing_yogyakarta_placement', 'Apakah Anda bersedia ditempatkan di seluruh cabang Manna Kampus Yogyakarta?', 'boolean', 1, 1, '1', 'equals', 910],
        ];

        $roleQuestions = [
            'ui-ux-designer' => [
                ['age', 'Usia saat melamar', 'number', 1, 1, '18-35', 'between', 10],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'D3/S1', 'minimum_education', 20],
                ['design_thinking', 'Apakah Anda memahami design thinking dan user research?', 'boolean', 1, 1, '1', 'equals', 30],
                ['design_tools', 'Apakah Anda menguasai tools desain dan prototyping?', 'boolean', 1, 1, '1', 'equals', 40],
                ['product_collaboration', 'Apakah Anda terbiasa berkolaborasi dengan tim product dan engineering?', 'boolean', 1, 0, '1', 'equals', 50],
            ],
            'content-marketing-specialist' => [
                ['age', 'Usia saat melamar', 'number', 1, 1, '18-35', 'between', 10],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'D3/S1', 'minimum_education', 20],
                ['copywriting_storytelling', 'Apakah Anda memiliki kemampuan copywriting dan storytelling?', 'boolean', 1, 1, '1', 'equals', 30],
                ['digital_content_strategy', 'Apakah Anda memahami strategi konten digital dan media sosial?', 'boolean', 1, 1, '1', 'equals', 40],
                ['target_oriented', 'Apakah Anda terbiasa bekerja secara terorganisir dengan target?', 'boolean', 1, 0, '1', 'equals', 50],
            ],
            'people-operations-intern' => [
                ['age', 'Usia saat melamar', 'number', 1, 1, '18-25', 'between', 10],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'D3/S1', 'minimum_education', 20],
                ['student_or_fresh_graduate', 'Apakah Anda mahasiswa tingkat akhir atau fresh graduate?', 'boolean', 1, 1, '1', 'equals', 30],
                ['data_confidentiality', 'Apakah Anda siap menjaga kerahasiaan data karyawan dan pelamar?', 'boolean', 1, 1, '1', 'equals', 40],
                ['people_operations_interest', 'Apakah Anda tertarik mempelajari people operations dan employee experience?', 'boolean', 1, 0, '1', 'equals', 50],
            ],
            'pramuniaga' => [
                ['gender', 'Jenis kelamin', 'choice', 1, 1, 'PRIA', 'equals', 10],
                ['age', 'Usia saat melamar', 'number', 1, 1, '18-25', 'between', 20],
                ['marital_status', 'Status pernikahan', 'choice', 1, 1, 'BELUM MENIKAH', 'equals', 30],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'SMA/SMK', 'minimum_education', 40],
            ],
            'programmer' => [
                ['age', 'Usia saat melamar', 'number', 1, 1, '18-40', 'between', 10],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'D3/S1', 'minimum_education', 20],
                ['relevant_it_major', 'Apakah pendidikan Anda relevan dengan bidang teknologi informasi atau sains?', 'boolean', 1, 1, '1', 'equals', 30],
                ['relevant_experience_years', 'Berapa tahun pengalaman Anda di bidang yang sama?', 'number', 1, 1, '1', 'greater_than_or_equal', 40],
                ['programming_skill', 'Apakah Anda menguasai pemrograman seperti Golang, Java, PostgreSQL, Laravel, atau PHP?', 'boolean', 1, 1, '1', 'equals', 50],
            ],
        ];

        $questionBuilder = $this->db->table('vacancy_screening_questions');

        foreach ($vacancyIds as $code => $vacancyId) {
            $questions = array_merge($roleQuestions[$code] ?? [], $genericQuestions);

            foreach ($questions as $question) {
                [$questionCode, $questionText, $answerType, $isRequired, $isKnockout, $expectedValue, $operator, $displayOrder] = $question;
                $exists = $questionBuilder
                    ->select('id')
                    ->where('vacancy_id', $vacancyId)
                    ->where('question_code', $questionCode)
                    ->get()
                    ->getRowArray();

                if ($exists !== null) {
                    $questionBuilder->where('id', $exists['id'])->update([
                        'question_text'       => $questionText,
                        'answer_type'         => $answerType,
                        'is_required'         => $isRequired,
                        'is_knockout'         => $isKnockout,
                        'expected_value'      => $expectedValue,
                        'comparison_operator' => $operator,
                        'display_order'       => $displayOrder,
                        'updated_at'          => $now,
                    ]);

                    continue;
                }

                $questionBuilder->insert([
                    'vacancy_id'         => $vacancyId,
                    'question_code'      => $questionCode,
                    'question_text'      => $questionText,
                    'answer_type'        => $answerType,
                    'is_required'        => $isRequired,
                    'is_knockout'        => $isKnockout,
                    'expected_value'     => $expectedValue,
                    'comparison_operator' => $operator,
                    'display_order'      => $displayOrder,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }
    }
}
