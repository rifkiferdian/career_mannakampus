<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Services;
use RuntimeException;
use Throwable;

/**
 * Creates one clearly marked, repeatable applicant journey for learning.
 *
 * Run with:
 * php spark db:seed SampleApplicantJourneySeeder
 */
class SampleApplicantJourneySeeder extends Seeder
{
    private const APPLICANT_UUID = '11111111-1111-4111-8111-111111111111';
    private const BATCH_UUID = '22222222-2222-4222-8222-222222222222';
    private const BATCH_NUMBER = 'SAMPLE-BATCH-001';
    private const EMAIL = 'budi.santoso.contoh@example.test';
    private const NIK = '3404071505980001';

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $nikHash = hash_hmac('sha256', self::NIK, (string) config('Encryption')->key);

        $vacancies = $this->vacancies();
        $this->ensureSampleDocuments();
        $this->db->transBegin();

        try {
            $applicantId = $this->upsertApplicant($nikHash, $now);
            $this->removePreviousJourney($applicantId);
            $batchId = $this->insertBatch($applicantId, $now);
            $this->insertDocuments($applicantId, $batchId, $now);

            $this->insertApplication(
                $applicantId,
                $batchId,
                $vacancies['programmer'],
                1,
                '33333333-3333-4333-8333-333333333331',
                'SAMPLE-APP-001',
                false,
                $now,
            );
            $this->insertApplication(
                $applicantId,
                $batchId,
                $vacancies['ui-ux-designer'],
                2,
                '33333333-3333-4333-8333-333333333332',
                'SAMPLE-APP-002',
                true,
                $now,
            );

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Database menolak sebagian data contoh.');
            }

            $this->db->transCommit();
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function vacancies(): array
    {
        $rows = $this->db->table('vacancies')
            ->whereIn('code', ['programmer', 'ui-ux-designer'])
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();
        $vacancies = array_column($rows, null, 'code');

        if (! isset($vacancies['programmer'], $vacancies['ui-ux-designer'])) {
            throw new RuntimeException(
                'Lowongan Programmer dan UI UX Designer wajib tersedia. Jalankan RecruitmentReferenceSeeder terlebih dahulu.',
            );
        }

        return $vacancies;
    }

    private function upsertApplicant(string $nikHash, string $now): int
    {
        $builder = $this->db->table('applicants');
        $applicant = $builder->where('uuid', self::APPLICANT_UUID)->get()->getRowArray();
        if ($applicant === null) {
            $applicant = $builder->where('email', self::EMAIL)->get()->getRowArray();
        }
        if ($applicant === null) {
            $applicant = $builder->where('nik_hash', $nikHash)->get()->getRowArray();
        }

        $data = [
            'uuid'                       => self::APPLICANT_UUID,
            'nik_hash'                   => $nikHash,
            'nik_encrypted'              => base64_encode(Services::encrypter()->encrypt(self::NIK)),
            'full_name'                  => 'Budi Santoso (Data Contoh)',
            'email'                      => self::EMAIL,
            'phone'                      => '6281234500001',
            'profile_photo_path'         => null,
            'birth_place'                => 'Sleman',
            'birth_date'                 => '1998-05-15',
            'height_cm'                  => 170,
            'gender'                     => 'PRIA',
            'marital_status'             => 'BELUM MENIKAH',
            'religion'                   => 'Islam',
            'address'                    => 'Jl. Data Contoh No. 1, Sleman, Daerah Istimewa Yogyakarta',
            'last_education'             => 'S1',
            'institution'                => 'Universitas Contoh Yogyakarta',
            'major'                      => 'Teknik Informatika',
            'gpa'                        => 3.65,
            'training_experience'        => 'Bootcamp Web Development dan pelatihan UI/UX dasar.',
            'privacy_consent_at'         => $now,
            'privacy_policy_version'     => '2026-07',
            'registration_ip'            => '127.0.0.1',
            'registration_user_agent'    => 'Seeder data contoh untuk pembelajaran alur database',
            'is_active'                  => 1,
            'updated_at'                 => $now,
            'deleted_at'                 => null,
        ];

        if ($applicant === null) {
            $data['created_at'] = $now;
            $builder->insert($data);

            return (int) $this->db->insertID();
        }

        $applicantId = (int) $applicant['id'];
        $builder->where('id', $applicantId)->update($data);

        return $applicantId;
    }

    private function removePreviousJourney(int $applicantId): void
    {
        $batch = $this->db->table('application_batches')
            ->select('id')
            ->where('batch_number', self::BATCH_NUMBER)
            ->get()
            ->getRowArray();

        if ($batch === null) {
            return;
        }

        $batchId = (int) $batch['id'];
        $applicationRows = $this->db->table('applications')
            ->select('id')
            ->where('batch_id', $batchId)
            ->get()
            ->getResultArray();
        $applicationIds = array_map('intval', array_column($applicationRows, 'id'));

        if ($applicationIds !== []) {
            $this->db->table('application_status_histories')->whereIn('application_id', $applicationIds)->delete();
            $this->db->table('application_screening_answers')->whereIn('application_id', $applicationIds)->delete();
            $this->db->table('applications')->whereIn('id', $applicationIds)->delete();
        }

        $this->db->table('applicant_documents')
            ->where('applicant_id', $applicantId)
            ->where('batch_id', $batchId)
            ->delete();
        $this->db->table('application_batches')->where('id', $batchId)->delete();
    }

    private function insertBatch(int $applicantId, string $now): int
    {
        $snapshot = [
            'snapshot_version' => '2026-07-v1',
            'captured_at'      => $now,
            'identity'         => [
                'nik_masked'     => '3404********0001',
                'full_name'      => 'Budi Santoso (Data Contoh)',
                'gender'         => 'PRIA',
                'birth_place'    => 'Sleman',
                'birth_date'     => '1998-05-15',
                'height_cm'      => 170,
                'marital_status' => 'BELUM MENIKAH',
                'religion'       => 'Islam',
            ],
            'contact' => [
                'email' => self::EMAIL,
                'phone' => '6281234500001',
            ],
            'address' => 'Jl. Data Contoh No. 1, Sleman, Daerah Istimewa Yogyakarta',
            'education' => [
                'level'               => 'S1',
                'institution'         => 'Universitas Contoh Yogyakarta',
                'major'               => 'Teknik Informatika',
                'gpa'                 => 3.65,
                'training_experience' => 'Bootcamp Web Development dan pelatihan UI/UX dasar.',
            ],
            'profile_photo_path' => null,
        ];

        $this->db->table('application_batches')->insert([
            'uuid'                 => self::BATCH_UUID,
            'batch_number'         => self::BATCH_NUMBER,
            'applicant_id'         => $applicantId,
            'position_count'       => 2,
            'applicant_snapshot'   => json_encode(
                $snapshot,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
            'snapshot_version'     => '2026-07-v1',
            'submitted_at'         => $now,
            'submitted_ip'         => '127.0.0.1',
            'submitted_user_agent' => 'Seeder data contoh untuk pembelajaran alur database',
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function insertDocuments(int $applicantId, int $batchId, string $now): void
    {
        $documents = [
            ['application_bundle', 'examples/sample-cv-budi-santoso.pdf', 'Berkas Lamaran Budi Santoso - Data Contoh.pdf'],
            ['supporting_documents', 'examples/sample-documents-budi-santoso.pdf', 'Dokumen Pendukung Budi Santoso - Data Contoh.pdf'],
        ];

        foreach ($documents as [$type, $path, $originalName]) {
            $absolutePath = WRITEPATH . 'uploads/' . $path;
            if (! is_file($absolutePath)) {
                throw new RuntimeException("Dokumen contoh tidak ditemukan: {$absolutePath}");
            }

            $this->db->table('applicant_documents')->insert([
                'applicant_id' => $applicantId,
                'batch_id'     => $batchId,
                'document_type'=> $type,
                'file_path'    => $path,
                'original_name'=> $originalName,
                'mime_type'    => 'application/pdf',
                'file_size'    => filesize($absolutePath),
                'created_at'   => $now,
            ]);
        }
    }

    private function ensureSampleDocuments(): void
    {
        $directory = WRITEPATH . 'uploads/examples';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Direktori dokumen contoh gagal dibuat: {$directory}");
        }

        $documents = [
            'sample-cv-budi-santoso.pdf' => ['CV Budi Santoso - Data Contoh', 'Dokumen simulasi alur rekrutmen.'],
            'sample-documents-budi-santoso.pdf' => ['Dokumen Pendukung - Data Contoh', 'Ijazah dan sertifikat hanya untuk simulasi.'],
        ];

        foreach ($documents as $filename => [$title, $description]) {
            $path = $directory . DIRECTORY_SEPARATOR . $filename;
            if (is_file($path)) {
                continue;
            }

            $content = $this->samplePdf($title, $description);
            if (file_put_contents($path, $content, LOCK_EX) === false) {
                throw new RuntimeException("Dokumen contoh gagal dibuat: {$path}");
            }
        }
    }

    private function samplePdf(string $title, string $description): string
    {
        $stream = "BT\n/F1 18 Tf\n72 720 Td\n({$title}) Tj\n"
            . "0 -30 Td\n/F1 11 Tf\n({$description}) Tj\nET\n";

        return "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] "
            . "/Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n"
            . "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n{$stream}endstream\nendobj\n"
            . "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n"
            . "trailer\n<< /Root 1 0 R /Size 6 >>\n%%EOF\n";
    }

    /**
     * @param array<string, mixed> $vacancy
     */
    private function insertApplication(
        int $applicantId,
        int $batchId,
        array $vacancy,
        int $preferenceOrder,
        string $uuid,
        string $applicationNumber,
        bool $failDesignTools,
        string $now,
    ): void {
        $questions = $this->db->table('vacancy_screening_questions')
            ->where('vacancy_id', (int) $vacancy['id'])
            ->orderBy('display_order', 'ASC')
            ->get()
            ->getResultArray();
        if ($questions === []) {
            throw new RuntimeException("Pertanyaan screening untuk {$vacancy['title']} belum tersedia.");
        }

        $answers = [];
        $eligibleCount = 0;
        $failedKnockout = [];
        foreach ($questions as $question) {
            $answer = $this->answerFor((string) $question['question_code'], $failDesignTools);
            $eligible = $this->answerMatches(
                $answer,
                (string) ($question['expected_value'] ?? ''),
                (string) ($question['comparison_operator'] ?? ''),
            );
            $eligibleCount += $eligible ? 1 : 0;
            if (! $eligible && (int) $question['is_knockout'] === 1) {
                $failedKnockout[] = (string) $question['question_code'];
            }
            $answers[] = [
                'question_id' => (int) $question['id'],
                'answer_value'=> $answer,
                'is_eligible' => $eligible ? 1 : 0,
                'score'       => $eligible ? 100 : 0,
            ];
        }

        $passed = $failedKnockout === [];
        $score = round(($eligibleCount / count($questions)) * 100, 2);
        $applicationStatus = $passed ? 'screening_passed' : 'screening_failed';
        $notes = $passed
            ? 'Semua kriteria knockout terpenuhi.'
            : 'Tidak memenuhi: ' . implode(', ', $failedKnockout);
        $period = $this->db->table('vacancy_recruitment_periods')
            ->select('id')
            ->where('vacancy_id', (int) $vacancy['id'])
            ->where('deleted_at', null)
            ->orderBy('status = "open"', 'DESC', false)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();
        if ($period === null) {
            throw new RuntimeException('Sesi lowongan belum tersedia untuk ' . $vacancy['title'] . '.');
        }

        $this->db->table('applications')->insert([
            'uuid'                 => $uuid,
            'application_number'   => $applicationNumber,
            'tracking_token_hash'  => hash('sha256', 'tracking-' . $applicationNumber),
            'batch_id'             => $batchId,
            'applicant_id'         => $applicantId,
            'vacancy_id'           => (int) $vacancy['id'],
            'vacancy_period_id'    => (int) $period['id'],
            'preference_order'     => $preferenceOrder,
            'work_experience'      => 'Dua tahun sebagai Junior Web Developer pada perusahaan contoh.',
            'skills'               => 'PHP, CodeIgniter, MySQL, JavaScript, Figma dasar.',
            'work_motivation'      => 'Ingin berkembang dan memberi kontribusi pada transformasi digital retail.',
            'career_goal'          => 'Menjadi software engineer yang memahami kebutuhan bisnis retail.',
            'screening_status'     => $passed ? 'passed' : 'failed',
            'screening_score'      => $score,
            'screening_notes'      => $notes,
            'public_message'       => $passed ? 'Lolos screening awal.' : 'Belum memenuhi screening awal.',
            'application_status'   => $applicationStatus,
            'submitted_at'         => $now,
            'submitted_ip'         => '127.0.0.1',
            'submitted_user_agent' => 'Seeder data contoh untuk pembelajaran alur database',
            'reviewed_at'          => null,
            'reviewed_by'          => null,
            'created_at'           => $now,
            'updated_at'           => $now,
            'deleted_at'           => null,
        ]);
        $applicationId = (int) $this->db->insertID();

        foreach ($answers as $answer) {
            $answer['application_id'] = $applicationId;
            $answer['created_at'] = $now;
            $answer['updated_at'] = $now;
            $this->db->table('application_screening_answers')->insert($answer);
        }

        $this->db->table('application_status_histories')->insert([
            'application_id' => $applicationId,
            'status_type'     => 'screening',
            'previous_status' => 'submitted',
            'new_status'      => $applicationStatus,
            'notes'           => 'Hasil screening otomatis dari data contoh.',
            'changed_by'      => null,
            'created_at'      => $now,
        ]);
    }

    private function answerFor(string $questionCode, bool $failDesignTools): string
    {
        return match ($questionCode) {
            'age'                       => '28',
            'education_level'           => 'S1',
            'relevant_experience_years' => '2',
            'gender'                    => 'PRIA',
            'marital_status'            => 'BELUM MENIKAH',
            'design_tools'              => $failDesignTools ? '0' : '1',
            default                     => '1',
        };
    }

    private function answerMatches(string $answer, string $expected, string $operator): bool
    {
        if ($expected === '' || $operator === '') {
            return true;
        }
        if ($operator === 'equals') {
            return mb_strtoupper($answer) === mb_strtoupper($expected);
        }
        if ($operator === 'between') {
            [$minimum, $maximum] = array_map('intval', explode('-', $expected, 2));
            return is_numeric($answer) && (float) $answer >= $minimum && (float) $answer <= $maximum;
        }
        if ($operator === 'greater_than_or_equal') {
            return is_numeric($answer) && (float) $answer >= (float) $expected;
        }
        if ($operator === 'minimum_education') {
            $rank = ['SMP' => 1, 'SMA' => 2, 'SMK' => 2, 'SMA/SMK' => 2, 'D1' => 3, 'D3' => 4, 'S1' => 5, 'S2' => 6];
            $levels = preg_split('/\//', mb_strtoupper($expected)) ?: [];
            $minimumRank = min(array_map(static fn (string $level): int => $rank[$level] ?? 99, $levels));

            return ($rank[mb_strtoupper($answer)] ?? 0) >= $minimumRank;
        }

        return true;
    }
}
