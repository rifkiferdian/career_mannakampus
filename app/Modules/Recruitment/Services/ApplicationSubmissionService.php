<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Recruitment\Models\ApplicantModel;
use App\Modules\Recruitment\Models\ApplicantDocumentModel;
use App\Modules\Recruitment\Models\ApplicationBatchModel;
use App\Modules\Recruitment\Models\ApplicationModel;
use App\Modules\Recruitment\Models\ScreeningAnswerModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use DomainException;
use RuntimeException;
use Throwable;

class ApplicationSubmissionService
{
    public function __construct(
        private readonly BaseConnection $database,
        private readonly ApplicantModel $applicantModel,
        private readonly ApplicationModel $applicationModel,
        private readonly ScreeningAnswerModel $answerModel,
        private readonly ApplicationBatchModel $batchModel,
        private readonly ApplicantDocumentModel $documentModel,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @param list<array<string, mixed>> $vacancies
     * @param array<string, UploadedFile|null> $files
     *
     * @return array{
     *     batch_number: string,
     *     screening_status: string,
     *     public_message: string,
     *     applications: list<array{title: string, application_number: string, screening_status: string, preference_order: int}>
     * }
     */
    public function submit(
        array $input,
        array $vacancies,
        array $files,
        string $ipAddress,
        string $userAgent,
    ): array {
        if ($vacancies === [] || count($vacancies) > 3) {
            throw new DomainException('Pilih minimal satu dan maksimal tiga posisi.');
        }

        $requirementGroupIds = array_unique(array_map(
            static fn (array $vacancy): int => (int) $vacancy['requirement_group_id'],
            $vacancies,
        ));
        if (count($requirementGroupIds) !== 1) {
            throw new DomainException('Semua posisi harus berasal dari kelompok persyaratan yang sama.');
        }

        $nik = preg_replace('/\D+/', '', (string) $input['nik']) ?? '';
        $nikHash = hash_hmac('sha256', $nik, (string) config('Encryption')->key);
        $now = date('Y-m-d H:i:s');
        $batchUuid = $this->uuid();
        $storedFiles = [];

        $this->database->transBegin();

        try {
            $applicant = $this->applicantModel->withDeleted()->where('nik_hash', $nikHash)->first();
            $emailOwner = $this->applicantModel->withDeleted()->where('email', strtolower((string) $input['email']))->first();

            if ($emailOwner !== null && ($applicant === null || (int) $emailOwner['id'] !== (int) $applicant['id'])) {
                throw new DomainException('Email sudah digunakan oleh pelamar lain.');
            }

            $photoPath = $applicant['profile_photo_path'] ?? null;
            if (($files['profile_photo'] ?? null)?->isValid()) {
                $photoPath = $this->storeFile($files['profile_photo'], $batchUuid, 'profile');
                $storedFiles[] = $photoPath;
            }

            $applicantData = [
                'nik_hash'              => $nikHash,
                'nik_encrypted'         => base64_encode(Services::encrypter()->encrypt($nik)),
                'full_name'             => trim((string) $input['full_name']),
                'email'                 => strtolower(trim((string) $input['email'])),
                'phone'                 => $this->normalizePhone((string) $input['phone']),
                'profile_photo_path'    => $photoPath,
                'birth_place'           => trim((string) $input['birth_place']),
                'birth_date'            => (string) $input['birth_date'],
                'height_cm'             => $input['height_cm'] !== '' ? (int) $input['height_cm'] : null,
                'gender'                => (string) $input['gender'],
                'marital_status'        => (string) $input['marital_status'],
                'religion'              => (string) $input['religion'],
                'address'               => trim((string) $input['address']),
                'last_education'        => (string) $input['last_education'],
                'institution'           => trim((string) $input['institution']),
                'major'                 => trim((string) $input['major']),
                'gpa'                   => $input['gpa'] !== '' ? (float) $input['gpa'] : null,
                'training_experience'   => trim((string) ($input['training_experience'] ?? '')),
                'privacy_consent_at'    => $now,
                'privacy_policy_version'=> '2026-07',
                'registration_ip'       => $ipAddress,
                'registration_user_agent' => mb_substr($userAgent, 0, 500),
                'is_active'             => 1,
                'deleted_at'            => null,
            ];

            if ($applicant === null) {
                $applicantData['uuid'] = $this->uuid();
                $applicantId = (int) $this->applicantModel->insert($applicantData, true);
            } else {
                $applicantId = (int) $applicant['id'];
                $this->applicantModel->update($applicantId, $applicantData);
            }

            $selectedPeriodIds = array_map(
                static fn (array $vacancy): int => (int) $vacancy['vacancy_period_id'],
                $vacancies,
            );
            $existingApplication = $this->applicationModel
                ->withDeleted()
                ->where('applicant_id', $applicantId)
                ->whereIn('vacancy_period_id', $selectedPeriodIds)
                ->first();

            if ($existingApplication !== null) {
                throw new DomainException('Salah satu posisi yang dipilih sudah pernah Anda lamar pada sesi ini.');
            }

            $activeApplicationCount = $this->database->table('applications AS applications')
                ->join('vacancy_recruitment_periods AS periods', 'periods.id = applications.vacancy_period_id')
                ->where('applications.applicant_id', $applicantId)
                ->where('applications.deleted_at', null)
                ->whereIn('periods.status', ['open', 'scheduled'])
                ->where('periods.deleted_at', null)
                ->groupStart()
                    ->where('periods.opened_at', null)
                    ->orWhere('periods.opened_at <=', $now)
                ->groupEnd()
                ->groupStart()
                    ->where('periods.closed_at', null)
                    ->orWhere('periods.closed_at >=', $now)
                ->groupEnd()
                ->countAllResults();

            if ($activeApplicationCount + count($vacancies) > 3) {
                throw new DomainException('Setiap pelamar hanya dapat memiliki maksimal tiga lamaran pada lowongan aktif.');
            }

            $batchNumber = 'MKB-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $batchId = (int) $this->batchModel->insert([
                'uuid'                 => $batchUuid,
                'batch_number'         => $batchNumber,
                'applicant_id'         => $applicantId,
                'requirement_group_id' => $requirementGroupIds[0],
                'position_count'       => count($vacancies),
                'applicant_snapshot'   => json_encode(
                    $this->applicantSnapshot($applicantData, $nik, $now),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
                'snapshot_version'     => '2026-07-v1',
                'submitted_at'         => $now,
                'submitted_ip'         => $ipAddress,
                'submitted_user_agent' => mb_substr($userAgent, 0, 500),
            ], true);

            $applicationBundle = $files['application_bundle'];
            $bundleMetadata = $this->fileMetadata($applicationBundle);
            $bundlePath = $this->storeFile($applicationBundle, $batchUuid, 'applications');
            $storedFiles[] = $bundlePath;
            $this->saveDocument($applicantId, $batchId, 'application_bundle', $bundlePath, $bundleMetadata, $now);

            $applicationResults = [];
            foreach ($vacancies as $vacancy) {
                $screeningAnswers = $this->screeningAnswers(
                    $vacancy['screening_questions'],
                    (array) ($input['screening'] ?? []),
                );
                $applicationNumber = 'MK-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                $applicationId = (int) $this->applicationModel->insert([
                    'uuid'                 => $this->uuid(),
                    'application_number'   => $applicationNumber,
                    'tracking_token_hash'  => hash('sha256', bin2hex(random_bytes(32))),
                    'batch_id'             => $batchId,
                    'applicant_id'         => $applicantId,
                    'vacancy_id'           => (int) $vacancy['id'],
                    'vacancy_period_id'    => (int) $vacancy['vacancy_period_id'],
                    'preference_order'      => (int) $vacancy['preference_order'],
                    'work_experience'      => trim((string) ($input['work_experience'] ?? '')),
                    'skills'               => trim((string) $input['skills']),
                    'work_motivation'      => trim((string) $input['work_motivation']),
                    'career_goal'          => trim((string) $input['career_goal']),
                    'screening_status'     => 'pending',
                    'screening_score'      => null,
                    'screening_notes'      => null,
                    'public_message'       => 'Lamaran Anda telah diterima dan menunggu pemeriksaan oleh tim HRD.',
                    'application_status'   => 'lamaran_baru',
                    'submitted_at'         => $now,
                    'submitted_ip'         => $ipAddress,
                    'submitted_user_agent' => mb_substr($userAgent, 0, 500),
                ], true);

                foreach ($screeningAnswers as $answer) {
                    $this->answerModel->insert([
                        'application_id' => $applicationId,
                        'question_id'    => $answer['question_id'],
                        'answer_value'   => $answer['answer_value'],
                        'is_eligible'    => null,
                        'score'          => null,
                    ]);
                }

                $this->database->table('application_status_histories')->insert([
                    'application_id'  => $applicationId,
                    'status_type'     => 'application',
                    'previous_status' => null,
                    'new_status'      => 'lamaran_baru',
                    'notes'           => 'Lamaran baru diterima dan menunggu screening manual oleh HRD.',
                    'changed_by'      => null,
                    'created_at'      => $now,
                ]);

                $applicationResults[] = [
                    'title'              => (string) $vacancy['title'],
                    'application_number' => $applicationNumber,
                    'screening_status'   => 'pending',
                    'preference_order'   => (int) $vacancy['preference_order'],
                ];
            }

            if ($this->database->transStatus() === false) {
                throw new RuntimeException('Penyimpanan lamaran gagal.');
            }

            $this->database->transCommit();

            return [
                'batch_number'     => $batchNumber,
                'screening_status' => 'pending',
                'public_message'   => 'Lamaran berhasil dikirim dan menunggu pemeriksaan oleh tim HRD.',
                'applications'     => $applicationResults,
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();
            foreach ($storedFiles as $storedFile) {
                $absolutePath = WRITEPATH . 'uploads/' . $storedFile;
                if (is_file($absolutePath)) {
                    unlink($absolutePath);
                }
            }

            throw $exception;
        }
    }

    /**
     * @param list<array<string, mixed>> $questions
     * @param array<string, mixed> $submittedAnswers
     *
     * @return list<array{question_id: int, answer_value: string}>
     */
    private function screeningAnswers(array $questions, array $submittedAnswers): array
    {
        $answers = [];

        foreach ($questions as $question) {
            $answers[] = [
                'question_id' => (int) $question['id'],
                'answer_value'=> trim((string) ($submittedAnswers[(string) $question['id']] ?? '')),
            ];
        }

        return $answers;
    }

    /**
     * @param array<string, mixed> $applicantData
     *
     * @return array<string, mixed>
     */
    private function applicantSnapshot(array $applicantData, string $nik, string $capturedAt): array
    {
        return [
            'snapshot_version' => '2026-07-v1',
            'captured_at'      => $capturedAt,
            'identity'         => [
                'nik_masked'     => substr($nik, 0, 4) . str_repeat('*', 8) . substr($nik, -4),
                'full_name'      => $applicantData['full_name'],
                'gender'         => $applicantData['gender'],
                'birth_place'    => $applicantData['birth_place'],
                'birth_date'     => $applicantData['birth_date'],
                'height_cm'      => $applicantData['height_cm'],
                'marital_status' => $applicantData['marital_status'],
                'religion'       => $applicantData['religion'],
            ],
            'contact'          => [
                'email' => $applicantData['email'],
                'phone' => $applicantData['phone'],
            ],
            'address'          => $applicantData['address'],
            'education'        => [
                'level'               => $applicantData['last_education'],
                'institution'         => $applicantData['institution'],
                'major'               => $applicantData['major'],
                'gpa'                 => $applicantData['gpa'],
                'training_experience' => $applicantData['training_experience'],
            ],
            'profile_photo_path' => $applicantData['profile_photo_path'],
        ];
    }

    /**
     * @return array{original_name: string, mime_type: string, file_size: int}
     */
    private function fileMetadata(UploadedFile $file): array
    {
        return [
            'original_name' => mb_substr($file->getClientName(), 0, 255),
            'mime_type'     => mb_substr($file->getClientMimeType(), 0, 100),
            'file_size'     => $file->getSize(),
        ];
    }

    /**
     * @param array{original_name: string, mime_type: string, file_size: int} $metadata
     */
    private function saveDocument(
        int $applicantId,
        int $batchId,
        string $documentType,
        string $filePath,
        array $metadata,
        string $createdAt,
    ): void {
        $this->documentModel->insert([
            'applicant_id'  => $applicantId,
            'batch_id'      => $batchId,
            'document_type' => $documentType,
            'file_path'     => $filePath,
            'original_name' => $metadata['original_name'],
            'mime_type'     => $metadata['mime_type'],
            'file_size'     => $metadata['file_size'],
            'created_at'    => $createdAt,
        ]);
    }

    private function storeFile(UploadedFile $file, string $applicationUuid, string $prefix): string
    {
        if (!$file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('Berkas unggahan tidak valid.');
        }

        $relativeDirectory = 'applications/' . $applicationUuid;
        $absoluteDirectory = WRITEPATH . 'uploads/' . $relativeDirectory;

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0700, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('Direktori unggahan tidak dapat dibuat.');
        }

        $filename = $prefix . '-' . bin2hex(random_bytes(8)) . '.' . $file->getExtension();
        $file->move($absoluteDirectory, $filename);

        return $relativeDirectory . '/' . $filename;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        return $phone;
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }
}
