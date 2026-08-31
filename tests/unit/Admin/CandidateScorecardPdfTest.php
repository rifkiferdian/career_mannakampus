<?php

namespace Tests\Unit\Admin;

use App\Modules\Admin\Services\CandidateScorecardPdf;
use PHPUnit\Framework\TestCase;

class CandidateScorecardPdfTest extends TestCase
{
    public function testItGeneratesMultipageScorecardWithApplicantAndRecommendation(): void
    {
        $answers = [];
        for ($index = 1; $index <= 35; $index++) {
            $answers[] = ['question_text' => 'Pertanyaan screening ' . $index, 'answer_value' => str_repeat('Jawaban kandidat ', 8)];
        }
        $pdf = (new CandidateScorecardPdf())->generate([
            'applicant' => [
                'full_name' => 'Budi Santoso', 'email' => 'budi@example.test', 'phone' => '081234567890',
                'birth_place' => 'Sleman', 'birth_date' => '1998-05-15', 'gender' => 'PRIA',
                'address' => str_repeat('Jalan Contoh ', 18), 'last_education' => 'S1',
                'institution' => 'Universitas Contoh', 'major' => 'Teknik Informatika', 'gpa' => '3.65',
            ],
            'applications' => [[
                'id' => 10, 'application_number' => 'MK-TEST-001', 'vacancy_title' => 'Programmer',
                'department_name' => 'Software Engineering', 'period_name' => 'Gelombang 1',
                'application_status' => 'hrd_interview', 'status_label' => 'Interview HRD',
                'submitted_at' => '2026-08-31 10:00:00', 'work_experience' => 'Dua tahun',
                'work_motivation' => 'Berkembang bersama perusahaan', 'career_goal' => 'Menjadi engineer senior',
            ]],
            'answers_by_application' => [10 => $answers],
            'recommendation' => ['recommendation' => 'continue', 'notes' => 'Direkomendasikan', 'updated_by_name' => 'Admin HRD', 'updated_at' => '2026-08-31 11:00:00'],
            'aspects' => [['name' => 'Kemampuan teknis', 'input_type' => 'scale_1_5', 'answer_value' => '5']],
            'printed_by' => 'Admin HRD', 'printed_at' => '31/08/2026 12:00',
            'logo_path' => ROOTPATH . 'public/assets/img/Logo_Manna_Kampus.png',
        ]);

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('SCORECARD KANDIDAT', $pdf);
        $this->assertStringContainsString('Budi Santoso', $pdf);
        $this->assertStringContainsString('MK-TEST-001', $pdf);
        $this->assertStringContainsString('Direkomendasikan', $pdf);
        $this->assertStringContainsString('0.949 0.420 0.086 rg', $pdf);
        if (extension_loaded('gd')) {
            $this->assertStringContainsString('/Subtype /Image', $pdf);
            $this->assertStringContainsString('/Logo Do', $pdf);
        }
        $this->assertMatchesRegularExpression('/\/Count ([2-9]|[1-9][0-9]+)/', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }
}
