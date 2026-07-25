<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RecruitmentReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $vacancies = [
            ['management-trainee', 'Management Trainee', 'Management', 'Full-time', 'D3', null, 35, 'draft'],
            ['programmer', 'Programmer', 'Product & Technology', 'Full-time', 'D3', null, 40, 'draft'],
            ['maintenance-engineering', 'Maintenance Engineering', 'Engineering', 'Full-time', null, null, null, 'draft'],
            ['supervisor-resto-roemi-lega-legi', 'Supervisor Resto Roemi Lega-Legi', 'Food & Beverage', 'Full-time', 'D3', null, 30, 'draft'],
            ['pramuniaga', 'Pramuniaga', 'Retail Operations', 'Full-time', 'SMA/SMK', null, 25, 'draft'],
            ['gudang', 'Gudang', 'Logistics', 'Full-time', null, null, null, 'draft'],
            ['security-care', 'Security Care', 'Security', 'Full-time', 'SMA/SMK', null, 35, 'draft'],
            ['cleaning-service', 'Cleaning Service', 'General Affairs', 'Full-time', 'SMP', null, 35, 'draft'],
            ['packing', 'Packing', 'Logistics', 'Full-time', null, null, null, 'draft'],
            ['teknisi', 'Teknisi', 'Engineering', 'Full-time', 'SMK', null, 35, 'draft'],
            ['waiters', 'Waiters', 'Food & Beverage', 'Full-time', null, null, null, 'draft'],
            ['cooking', 'Cooking', 'Food & Beverage', 'Full-time', null, null, null, 'draft'],
            ['butcher', 'Butcher', 'Fresh Food', 'Full-time', null, null, null, 'draft'],
            ['penjaga-rumah', 'Penjaga Rumah', 'General Affairs', 'Full-time', null, null, null, 'draft'],
            ['paid-internship-hrd', 'Paid Internship HRD', 'People Operations', 'Internship', null, null, null, 'draft'],
            ['staff-hrd', 'Staff HRD', 'People Operations', 'Full-time', null, null, null, 'draft'],
            ['staff-akuntansi', 'Staff Akuntansi', 'Finance & Accounting', 'Full-time', null, null, null, 'draft'],
            ['store-manager', 'Store Manager', 'Retail Operations', 'Full-time', null, null, null, 'draft'],
            ['staf-dapur', 'Staf Dapur', 'Food & Beverage', 'Full-time', null, null, null, 'draft'],
            ['kurir', 'Kurir', 'Logistics', 'Full-time', null, null, null, 'draft'],
            ['parkir', 'Parkir', 'General Affairs', 'Full-time', null, null, null, 'draft'],
            ['driver', 'Driver', 'Logistics', 'Full-time', null, null, null, 'draft'],
            ['manager-back-office', 'Manager Back Office', 'Management', 'Full-time', null, null, null, 'draft'],
            ['spv-marketing-sosial-media', 'SPV Marketing Sosial Media', 'Marketing', 'Full-time', null, null, null, 'draft'],
            ['spv-akuntansi', 'SPV Akuntansi', 'Finance & Accounting', 'Full-time', null, null, null, 'draft'],
            ['admin-office', 'Admin Office', 'Administration', 'Full-time', null, null, null, 'draft'],
            ['social-media-marketing-staff', 'Social Media & Marketing Staff', 'Marketing', 'Full-time', null, null, null, 'draft'],
            ['ui-ux-designer', 'UI UX Designer', 'Product & Technology', 'Full-time', null, null, null, 'open'],
            ['content-marketing-specialist', 'Content Marketing Specialist', 'Marketing', 'Full-time', null, null, null, 'open'],
            ['people-operations-intern', 'People Operations Intern', 'People Operations', 'Internship', null, null, null, 'open'],
        ];

        $vacancyIds = [];
        $vacancyBuilder = $this->db->table('vacancies');

        foreach ($vacancies as $vacancy) {
            [$code, $title, $department, $employmentType, $minimumEducation, $minimumAge, $maximumAge, $status] = $vacancy;
            $existing = $vacancyBuilder->select('id')->where('code', $code)->get()->getRowArray();

            if ($existing === null) {
                $vacancyBuilder->insert([
                    'code'              => $code,
                    'title'             => $title,
                    'department'        => $department,
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
            }
        }

        $genericQuestions = [
            ['willing_shift', 'Apakah Anda bersedia bekerja dengan sistem shift?', 'boolean', 1, 1, '1', 'equals', 900],
            ['willing_yogyakarta_placement', 'Apakah Anda bersedia ditempatkan di seluruh cabang Manna Kampus Yogyakarta?', 'boolean', 1, 1, '1', 'equals', 910],
        ];

        $roleQuestions = [
            'pramuniaga' => [
                ['gender', 'Jenis kelamin', 'choice', 1, 1, 'PRIA', 'equals', 10],
                ['age', 'Usia saat melamar', 'number', 1, 1, '25', 'less_than_or_equal', 20],
                ['marital_status', 'Status pernikahan', 'choice', 1, 1, 'BELUM MENIKAH', 'equals', 30],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'SMA/SMK', 'minimum_education', 40],
            ],
            'security-care' => [
                ['age', 'Usia saat melamar', 'number', 1, 1, '35', 'less_than_or_equal', 10],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'SMA/SMK', 'minimum_education', 20],
                ['height_cm', 'Tinggi badan dalam sentimeter', 'number', 1, 1, '160', 'greater_than_or_equal', 30],
                ['security_certificate', 'Apakah Anda memiliki sertifikat Security/Satpam?', 'boolean', 1, 0, '1', 'equals', 40],
            ],
            'cleaning-service' => [
                ['gender', 'Jenis kelamin', 'choice', 1, 1, 'PRIA', 'equals', 10],
                ['age', 'Usia saat melamar', 'number', 1, 1, '35', 'less_than_or_equal', 20],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'SMP', 'minimum_education', 30],
            ],
            'teknisi' => [
                ['gender', 'Jenis kelamin', 'choice', 1, 1, 'PRIA', 'equals', 10],
                ['age', 'Usia saat melamar', 'number', 1, 1, '35', 'less_than_or_equal', 20],
                ['technical_education', 'Apakah Anda lulusan SMK jurusan Listrik, Audio Video, Elektronika Industri, atau Teknik Elektro?', 'boolean', 1, 1, '1', 'equals', 30],
                ['healthy_vision', 'Apakah penglihatan Anda sehat dan tidak rabun dekat, rabun jauh, atau silinder?', 'boolean', 1, 1, '1', 'equals', 40],
            ],
            'programmer' => [
                ['age', 'Usia saat melamar', 'number', 1, 1, '40', 'less_than_or_equal', 10],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'D3', 'minimum_education', 20],
                ['relevant_it_major', 'Apakah pendidikan Anda relevan dengan bidang teknologi informasi atau sains?', 'boolean', 1, 1, '1', 'equals', 30],
                ['relevant_experience_years', 'Berapa tahun pengalaman Anda di bidang yang sama?', 'number', 1, 1, '1', 'greater_than_or_equal', 40],
                ['programming_skill', 'Apakah Anda menguasai pemrograman seperti Golang, Java, PostgreSQL, Laravel, atau PHP?', 'boolean', 1, 1, '1', 'equals', 50],
            ],
            'management-trainee' => [
                ['age', 'Usia saat melamar', 'number', 1, 1, '35', 'less_than_or_equal', 10],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'D3', 'minimum_education', 20],
                ['leadership_problem_solving', 'Apakah Anda memiliki kemampuan leadership dan problem solving yang baik?', 'boolean', 1, 1, '1', 'equals', 30],
                ['willing_mt_placement', 'Apakah Anda bersedia ditempatkan sebagai Supervisor Toko, Supervisor Gudang, atau Purchase setelah program?', 'boolean', 1, 1, '1', 'equals', 40],
            ],
            'supervisor-resto-roemi-lega-legi' => [
                ['age', 'Usia saat melamar', 'number', 1, 1, '30', 'less_than_or_equal', 10],
                ['education_level', 'Pendidikan terakhir', 'education_level', 1, 1, 'D3', 'minimum_education', 20],
                ['leadership_problem_solving', 'Apakah Anda memiliki kemampuan leadership dan problem solving yang baik?', 'boolean', 1, 1, '1', 'equals', 30],
                ['restaurant_operations', 'Apakah Anda mampu mengontrol operasional restoran dan mengawasi aktivitas layanan serta dapur?', 'boolean', 1, 1, '1', 'equals', 40],
                ['willing_resto_placement', 'Apakah Anda bersedia ditempatkan di Resto Lega Legi?', 'boolean', 1, 1, '1', 'equals', 50],
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
                    'comparison_operator'=> $operator,
                    'display_order'      => $displayOrder,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }
    }
}
