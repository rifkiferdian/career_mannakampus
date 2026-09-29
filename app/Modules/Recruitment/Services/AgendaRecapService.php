<?php

namespace App\Modules\Recruitment\Services;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;

class AgendaRecapService
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    public static function filters(array $input): array
    {
        $text = static fn (string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
        $date = static function (string $value, string $fallback): string {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            return $parsed !== false && $parsed->format('Y-m-d') === $value ? $value : $fallback;
        };
        $from = $date($text('date_from'), date('Y-m-01'));
        $to = $date($text('date_to'), date('Y-m-t'));
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        return ['date_from' => $from, 'date_to' => $to, 'stage_id' => max(0, (int) $text('stage_id')),
            'pic_user_id' => max(0, (int) $text('pic_user_id')),
            'status' => array_key_exists($text('status'), RecruitmentSessionService::STATUSES) ? $text('status') : ''];
    }

    private function sessions(int $userId, array $filters): BaseBuilder
    {
        $query = (new RecruitmentSessionService($this->db))->visibleSessions($userId)
            ->where('sessions.starts_at >=', $filters['date_from'] . ' 00:00:00')
            ->where('sessions.starts_at <=', $filters['date_to'] . ' 23:59:59');
        foreach (['stage_id', 'pic_user_id', 'status'] as $key) {
            if (! empty($filters[$key])) {
                $query->where('sessions.' . $key, $filters[$key]);
            }
        }
        return $query;
    }

    private function counts(): string
    {
        return "COUNT(schedules.id) AS participants,
            COALESCE(SUM(CASE WHEN schedules.status = 'present' THEN 1 ELSE 0 END), 0) AS present,
            COALESCE(SUM(CASE WHEN schedules.status = 'absent' THEN 1 ELSE 0 END), 0) AS absent,
            COALESCE(SUM(CASE WHEN schedules.status IN ('scheduled','confirmed','reschedule_requested') THEN 1 ELSE 0 END), 0) AS pending,
            COALESCE(SUM(CASE WHEN schedules.status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled";
    }

    public function summary(int $userId, array $filters): array
    {
        return $this->sessions($userId, $filters)
            ->join('recruitment_schedules AS schedules', 'schedules.session_id = sessions.id', 'left')
            ->join('applications', 'applications.id = schedules.application_id', 'left')
            ->select('COUNT(DISTINCT sessions.id) AS agendas, COUNT(DISTINCT applications.applicant_id) AS unique_applicants, ' . $this->counts(), false)
            ->get()->getRowArray();
    }

    public function rows(int $userId, array $filters, ?int $limit = null, int $offset = 0): array
    {
        $counts = $this->db->table('recruitment_schedules AS schedules')->select('schedules.session_id, ' . $this->counts(), false)
            ->groupBy('schedules.session_id')->getCompiledSelect();
        $query = $this->sessions($userId, $filters)
            ->select('sessions.id, sessions.name, sessions.starts_at, sessions.status, stages.name AS stage_name, pic.full_name AS pic_name')
            ->join('recruitment_stages AS stages', 'stages.id = sessions.stage_id')
            ->join('users AS pic', 'pic.id = sessions.pic_user_id')
            ->join('(' . $counts . ') AS totals', 'totals.session_id = sessions.id', 'left', false);
        foreach (['participants', 'present', 'absent', 'pending', 'cancelled'] as $key) {
            $query->select('COALESCE(totals.' . $key . ', 0) AS ' . $key, false);
        }
        $query->orderBy('sessions.starts_at', 'DESC')->orderBy('sessions.id', 'DESC');
        if ($limit !== null) {
            $query->limit($limit, $offset);
        }
        return $query->get()->getResultArray();
    }
}
