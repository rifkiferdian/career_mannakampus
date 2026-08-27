<?php

namespace App\Modules\Recruitment\Services;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;

class ApplicantBlacklistService
{
    public function __construct(
        private readonly BaseConnection $database,
    ) {
    }

    public function isActive(int $applicantId, ?string $now = null): bool
    {
        return $this->activeFor($applicantId, $now) !== null;
    }

    /** @return array<string, mixed>|null */
    public function activeFor(int $applicantId, ?string $now = null): ?array
    {
        if ($applicantId <= 0) {
            return null;
        }

        $now ??= date('Y-m-d H:i:s');

        return $this->database->table('applicant_blacklists')
            ->where('applicant_id', $applicantId)
            ->where('revoked_at', null)
            ->where('starts_at <=', $now)
            ->groupStart()
                ->where('is_permanent', 1)
                ->orWhere('ends_at >=', $now)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray() ?: null;
    }

    /** @param array<string, mixed> $blacklist */
    public static function statusOf(array $blacklist, ?DateTimeImmutable $now = null): string
    {
        if (! empty($blacklist['revoked_at'])) {
            return 'revoked';
        }
        if ((int) ($blacklist['is_permanent'] ?? 0) === 1) {
            return 'permanent';
        }

        $now ??= new DateTimeImmutable();
        $end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) ($blacklist['ends_at'] ?? ''));

        return $end === false || $end < $now ? 'expired' : 'active';
    }
}
