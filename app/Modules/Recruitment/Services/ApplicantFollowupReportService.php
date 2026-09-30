<?php

namespace App\Modules\Recruitment\Services;

use App\Modules\Admin\Services\AuthorizationService;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;

class ApplicantFollowupReportService
{
    public const CONDITIONS = ['screening' => 'Belum screening', 'decision' => 'Menunggu keputusan', 'schedule' => 'Menunggu jadwal'];

    public function __construct(private readonly BaseConnection $db)
    {
    }

    public static function filters(array $input): array
    {
        $text = static fn (string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
        $dates = [];
        foreach (['date_from', 'date_to'] as $key) {
            $value = $text($key);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            $dates[$key] = $date !== false && $date->format('Y-m-d') === $value ? $value : '';
        }
        if ($dates['date_from'] !== '' && $dates['date_to'] !== '' && $dates['date_from'] > $dates['date_to']) {
            [$dates['date_from'], $dates['date_to']] = [$dates['date_to'], $dates['date_from']];
        }
        return $dates + ['keyword' => mb_substr($text('keyword'), 0, 100), 'vacancy_id' => max(0, (int) $text('vacancy_id')),
            'team_id' => max(0, (int) $text('team_id')), 'stage_id' => max(0, (int) $text('stage_id')),
            'min_days' => min(36500, max(0, (int) $text('min_days'))),
            'condition' => array_key_exists($text('condition'), self::CONDITIONS) ? $text('condition') : '',
            'unassigned' => $text('unassigned') === '1' ? '1' : ''];
    }

    public static function stageCode(string $code): string
    {
        return match ($code) {
            'reviewed' => 'under_review', 'interview_hr', 'interview_scheduled' => 'hrd_interview',
            'interview_user' => 'user_interview', 'hired' => 'accepted', default => $code,
        };
    }

    /** Histories and schedules must be ordered newest first. */
    public static function classify(array $application, array $schedules, array $histories, array $stages, string $mode, string $now): ?array
    {
        $code = self::stageCode($application['application_status']);
        $stage = $stages[$code] ?? null;
        if ($mode === 'screening') {
            if (! in_array($code, ['lamaran_baru', 'submitted', 'under_review', 'document_screening'], true)
                || in_array($application['screening_status'] ?? '', ['passed', 'failed'], true)) {
                return null;
            }
            return ['condition' => 'screening', 'reference_at' => $application['submitted_at'], 'reference_label' => 'Tanggal melamar',
                'last_stage' => $stage['name'] ?? 'Lamaran baru', 'next_stage' => 'Screening berkas', 'pic_name' => null];
        }
        if ($stage === null || ! (int) $stage['is_schedulable'] || in_array($code, ['document_screening', 'under_review', 'accepted', 'rejected', 'withdrawn', 'screening_failed'], true)) {
            return null;
        }
        $transition = null;
        foreach ($histories as $history) {
            if ($history['status_type'] === 'application' && self::stageCode($history['new_status']) === $code
                && self::stageCode((string) $history['previous_status']) !== $code) {
                $transition = $history;
                break;
            }
        }
        foreach ($schedules as $schedule) {
            if (in_array($schedule['status'], RecruitmentScheduleService::ACTIVE_STATUSES, true)) {
                return null;
            }
        }
        $current = null;
        foreach ($schedules as $schedule) {
            if ((int) $schedule['stage_id'] === (int) $stage['id']
                && ($transition === null || $schedule['scheduled_at'] >= $transition['created_at'])) {
                $current = $schedule;
                break;
            }
        }
        if ($current !== null && $current['status'] === 'present' && $current['scheduled_at'] <= $now) {
            return ['condition' => 'decision', 'reference_at' => $current['scheduled_at'], 'reference_label' => 'Pelaksanaan (tercatat hadir)',
                'last_stage' => $stage['name'], 'next_stage' => 'Putuskan lolos / gugur', 'pic_name' => $current['pic_name'] ?? null];
        }
        // Missing/absent attendance is not evidence that a stage has been completed.
        if ($current !== null && $current['status'] !== 'cancelled') {
            return null;
        }
        $previous = $transition === null ? null : ($stages[self::stageCode((string) $transition['previous_status'])] ?? null);
        if ($previous === null || ! (int) $previous['is_schedulable'] || $transition['created_at'] > $now) {
            return null;
        }
        foreach ($schedules as $schedule) {
            if ((int) $schedule['stage_id'] === (int) $previous['id']) {
                if ($schedule['status'] !== 'present' || $schedule['scheduled_at'] > $transition['created_at']) {
                    return null;
                }
                return ['condition' => 'schedule', 'reference_at' => $transition['created_at'], 'reference_label' => 'Perpindahan ke tahap berikutnya',
                    'last_stage' => $previous['name'], 'next_stage' => $stage['name'], 'pic_name' => $schedule['pic_name'] ?? null];
            }
        }
        return null;
    }

    public function report(int $userId, string $mode, array $filters): array
    {
        $authorization = new AuthorizationService($this->db);
        $allTeams = $authorization->can($userId, 'hrd.teams.manage');
        $teamIds = array_column($this->db->table('hrd_team_users')->where('user_id', $userId)->get()->getResultArray(), 'hrd_team_id');
        $teamsQuery = $this->db->table('hrd_teams')->select('id, name')->orderBy('name');
        if (! $allTeams) {
            $teamsQuery->whereIn('id', $teamIds ?: [0]);
        }
        $teams = $teamsQuery->get()->getResultArray();
        $query = $this->db->table('applications AS applications')
            ->select('applications.id, applications.applicant_id, applications.vacancy_id, applications.application_number, applications.application_status, applications.screening_status, applications.submitted_at, applicants.full_name, applicants.assigned_hrd_team_id, vacancies.title AS vacancy_title, teams.name AS team_name')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('hrd_teams AS teams', 'teams.id = applicants.assigned_hrd_team_id', 'left')
            ->where('applications.deleted_at', null)->where('applicants.deleted_at', null)
            ->whereNotIn('applications.application_status', ['accepted', 'hired', 'rejected', 'withdrawn', 'screening_failed']);
        if ($mode === 'screening') {
            $now = $this->db->escape(date('Y-m-d H:i:s'));
            $query->where("NOT EXISTS (SELECT 1 FROM applicant_blacklists AS active_blacklist
                WHERE active_blacklist.applicant_id = applicants.id
                AND active_blacklist.revoked_at IS NULL
                AND active_blacklist.starts_at <= {$now}
                AND (active_blacklist.is_permanent = 1 OR active_blacklist.ends_at >= {$now}))", null, false);
        } else {
            $now = $this->db->escape(date('Y-m-d H:i:s'));
            $query->where("NOT EXISTS (SELECT 1 FROM applicant_blacklists AS active_blacklist
                WHERE active_blacklist.applicant_id = applicants.id
                AND active_blacklist.revoked_at IS NULL
                AND active_blacklist.starts_at <= {$now}
                AND (active_blacklist.is_permanent = 1 OR active_blacklist.ends_at >= {$now}))", null, false);
        }
        if (! $allTeams) {
            $query->groupStart()->whereIn('applicants.assigned_hrd_team_id', $teamIds ?: [0]);
            if ($mode === 'screening' && $authorization->can($userId, 'applicants.pool.view')) {
                $query->orWhere('applicants.assigned_hrd_team_id', null);
            }
            $query->groupEnd();
        }
        // Options come only from applications within the user's scope.
        $vacancies = (clone $query)->select('vacancies.id AS option_id')->get()->getResultArray();
        $options = [];
        foreach ($vacancies as $row) {
            $options[$row['option_id']] = ['id' => $row['option_id'], 'title' => $row['vacancy_title']];
        }
        usort($options, static fn (array $a, array $b): int => strcasecmp($a['title'], $b['title']));
        if ($filters['keyword'] !== '') {
            $query->groupStart()->like('applicants.full_name', $filters['keyword'])->orLike('applications.application_number', $filters['keyword'])->groupEnd();
        }
        foreach (['vacancy_id' => 'applications.vacancy_id', 'team_id' => 'applicants.assigned_hrd_team_id'] as $key => $column) {
            if ($filters[$key] > 0) {
                $query->where($column, $filters[$key]);
            }
        }
        if ($mode === 'screening' && $filters['unassigned'] === '1') {
            $query->where('applicants.assigned_hrd_team_id', null);
        }
        $stages = array_column($this->db->table('recruitment_stages')->get()->getResultArray(), null, 'code');
        $applications = $query->orderBy('applications.id')->get()->getResultArray();
        $rows = [];
        $now = date('Y-m-d H:i:s');
        foreach (array_chunk($applications, 500) as $chunk) {
            $ids = array_column($chunk, 'id');
            $schedules = $histories = [];
            if ($mode !== 'screening') {
                foreach ($this->db->table('recruitment_schedules AS schedules')->select('schedules.*, pic.full_name AS pic_name')
                    ->join('users AS pic', 'pic.id = schedules.pic_user_id', 'left')->whereIn('application_id', $ids)->orderBy('schedules.id', 'DESC')->get()->getResultArray() as $row) {
                    $schedules[$row['application_id']][] = $row;
                }
                foreach ($this->db->table('application_status_histories')->whereIn('application_id', $ids)->where('status_type', 'application')
                    ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray() as $row) {
                    $histories[$row['application_id']][] = $row;
                }
            }
            foreach ($chunk as $application) {
                $stage = $stages[self::stageCode($application['application_status'])] ?? null;
                if ($filters['stage_id'] > 0 && (int) ($stage['id'] ?? 0) !== $filters['stage_id']) {
                    continue;
                }
                $result = self::classify($application, $schedules[$application['id']] ?? [], $histories[$application['id']] ?? [], $stages, $mode, $now);
                if ($result === null || empty($result['reference_at']) || $result['reference_at'] > $now) {
                    continue;
                }
                $reference = substr($result['reference_at'], 0, 10);
                $days = (int) (new DateTimeImmutable($result['reference_at']))->diff(new DateTimeImmutable($now))->format('%a');
                if (($filters['condition'] !== '' && $result['condition'] !== $filters['condition']) || $days < $filters['min_days']
                    || ($filters['date_from'] !== '' && $reference < $filters['date_from']) || ($filters['date_to'] !== '' && $reference > $filters['date_to'])) {
                    continue;
                }
                $rows[] = $application + $result + ['wait_days' => $days];
            }
        }
        usort($rows, static fn (array $a, array $b): int => strcmp($a['reference_at'], $b['reference_at']) ?: $a['id'] <=> $b['id']);
        $summary = ['total' => count($rows), 'decision' => 0, 'schedule' => 0, 'unassigned' => 0, 'longest' => 0];
        foreach ($rows as $row) {
            if (isset($summary[$row['condition']])) {
                $summary[$row['condition']]++;
            }
            $summary['unassigned'] += empty($row['assigned_hrd_team_id']) ? 1 : 0;
            $summary['longest'] = max($summary['longest'], $row['wait_days']);
        }
        return ['rows' => $rows, 'summary' => $summary, 'teams' => $teams, 'vacancies' => $options,
            'stages' => array_values(array_filter($stages, static fn (array $stage): bool => (int) $stage['is_schedulable'] === 1))];
    }
}
