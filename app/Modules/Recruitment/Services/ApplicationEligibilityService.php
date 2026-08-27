<?php

namespace App\Modules\Recruitment\Services;

use CodeIgniter\Database\BaseConnection;
use DateInterval;
use DateTimeImmutable;

class ApplicationEligibilityService
{
    public function __construct(
        private readonly BaseConnection $database,
        private readonly ApplicantBlacklistService $blacklistService,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function restrictionFor(int $applicantId, ?DateTimeImmutable $now = null): ?array
    {
        if ($applicantId <= 0) {
            return null;
        }

        $now ??= new DateTimeImmutable();
        $blacklist = $this->blacklistService->activeFor($applicantId, $now->format('Y-m-d H:i:s'));
        if ($blacklist !== null) {
            return [
                'type' => 'blacklist',
                'source' => 'applicant',
                'reference' => sprintf('BLP-%06d', (int) $blacklist['id']),
                'matched_identifier' => 'NIK',
                'identifier_hint' => '',
                'is_permanent' => (int) $blacklist['is_permanent'] === 1,
                'ends_at' => $blacklist['ends_at'] ?? null,
            ];
        }

        $writtenTestOrders = '(SELECT links.template_id, MIN(links.display_order) AS first_written_test_order
            FROM recruitment_process_template_stages links
            INNER JOIN recruitment_stages written_stages ON written_stages.id = links.stage_id
            WHERE written_stages.code = \'written_test\' OR written_stages.code LIKE \'written_test_%\'
            GROUP BY links.template_id) AS written_test_orders';

        $rejection = $this->database->table('application_rejections AS rejections')
            ->select('rejections.rejected_at, rejections.stage_name_snapshot')
            ->join('applications AS applications', 'applications.id = rejections.application_id')
            ->join('vacancies AS vacancies', 'vacancies.id = applications.vacancy_id')
            ->join($writtenTestOrders, 'written_test_orders.template_id = vacancies.recruitment_process_template_id', 'inner', false)
            ->where('applications.applicant_id', $applicantId)
            ->where('rejections.stage_order_snapshot >= written_test_orders.first_written_test_order', null, false)
            ->orderBy('rejections.rejected_at', 'DESC')
            ->orderBy('rejections.id', 'DESC')
            ->get(1)
            ->getRowArray();

        if ($rejection === null) {
            return null;
        }

        $rejectedAt = new DateTimeImmutable((string) $rejection['rejected_at']);
        $availableAt = self::availableAt($rejectedAt);
        if ($now >= $availableAt) {
            return null;
        }

        return [
            'type' => 'cooldown',
            'rejected_at' => $rejectedAt->format('Y-m-d H:i:s'),
            'available_at' => $availableAt->format('Y-m-d H:i:s'),
            'stage_name' => (string) $rejection['stage_name_snapshot'],
        ];
    }

    public static function availableAt(DateTimeImmutable $rejectedAt): DateTimeImmutable
    {
        return $rejectedAt->add(new DateInterval('P3M'));
    }
}
