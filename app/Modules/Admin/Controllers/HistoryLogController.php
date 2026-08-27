<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use DateTimeImmutable;

class HistoryLogController extends BaseController
{
    private const PER_PAGE = 50;

    private const SOURCES = [
        'security' => 'Keamanan akun',
        'application' => 'Status lamaran',
        'assignment' => 'Assignment HRD',
        'blacklist' => 'Blacklist',
        'schedule' => 'Jadwal seleksi',
        'talent_pool' => 'Talent Pool',
    ];

    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $filters = $this->filters();
        [$whereSql, $bindings] = $this->whereClause($filters);
        $unionSql = $this->unionSql();

        $countRow = $database->query(
            'SELECT COUNT(*) AS total FROM (' . $unionSql . ') AS audit_logs' . $whereSql,
            $bindings
        )->getRowArray();
        $total = (int) ($countRow['total'] ?? 0);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) $this->request->getGet('page')), $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;

        $logs = $database->query(
            'SELECT * FROM (' . $unionSql . ') AS audit_logs'
                . $whereSql
                . ' ORDER BY occurred_at DESC, source ASC, row_key DESC'
                . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset,
            $bindings
        )->getResultArray();

        $users = $database->table('users')
            ->select('id, full_name, email')
            ->where('deleted_at', null)
            ->orderBy('full_name', 'ASC')
            ->get()
            ->getResultArray();

        return view('admin/history_logs', [
            'auth' => session()->get('hrd_auth'),
            'logs' => $logs,
            'users' => $users,
            'sources' => self::SOURCES,
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'total' => $total,
                'total_pages' => $totalPages,
                'offset' => $offset,
            ],
        ]);
    }

    /** @return array{keyword: string, source: string, user_id: int, date_from: string, date_to: string} */
    private function filters(): array
    {
        $source = trim((string) $this->request->getGet('source'));
        if (! isset(self::SOURCES[$source])) {
            $source = '';
        }

        return [
            'keyword' => mb_substr(trim((string) $this->request->getGet('keyword')), 0, 100),
            'source' => $source,
            'user_id' => max(0, (int) $this->request->getGet('user_id')),
            'date_from' => $this->validDate((string) $this->request->getGet('date_from')),
            'date_to' => $this->validDate((string) $this->request->getGet('date_to')),
        ];
    }

    private function validDate(string $value): string
    {
        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    /**
     * @param array{keyword: string, source: string, user_id: int, date_from: string, date_to: string} $filters
     * @return array{string, list<mixed>}
     */
    private function whereClause(array $filters): array
    {
        $conditions = [];
        $bindings = [];
        if ($filters['source'] !== '') {
            $conditions[] = 'source = ?';
            $bindings[] = $filters['source'];
        }
        if ($filters['user_id'] > 0) {
            $conditions[] = 'actor_id = ?';
            $bindings[] = $filters['user_id'];
        }
        if ($filters['date_from'] !== '') {
            $conditions[] = 'occurred_at >= ?';
            $bindings[] = $filters['date_from'] . ' 00:00:00';
        }
        if ($filters['date_to'] !== '') {
            $conditions[] = 'occurred_at < ?';
            $bindings[] = (new DateTimeImmutable($filters['date_to']))->modify('+1 day')->format('Y-m-d 00:00:00');
        }
        if ($filters['keyword'] !== '') {
            $conditions[] = '(subject LIKE ? OR reference_text LIKE ? OR description LIKE ? OR actor_name LIKE ?)';
            $keyword = '%' . $filters['keyword'] . '%';
            array_push($bindings, $keyword, $keyword, $keyword, $keyword);
        }

        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $bindings];
    }

    private function unionSql(): string
    {
        return <<<'SQL'
SELECT 'security' AS source, CAST(history.id AS CHAR) AS row_key, history.event_type AS action,
       history.email AS subject, CONCAT('IP ', history.ip_address) AS reference_text,
       CONCAT(IF(history.was_successful = 1, 'Berhasil', 'Gagal'), ' · ', history.device_label) AS description,
       history.user_id AS actor_id, COALESCE(users.full_name COLLATE utf8mb4_general_ci, history.email, 'Sistem') AS actor_name,
       history.occurred_at AS occurred_at
FROM user_login_history AS history
LEFT JOIN users ON users.id = history.user_id
UNION ALL
SELECT 'application', CAST(history.id AS CHAR), history.status_type,
       applicants.full_name COLLATE utf8mb4_general_ci, CONCAT_WS(' · ', applications.application_number, vacancies.title),
       CONCAT(COALESCE(history.previous_status, 'Awal'), ' → ', history.new_status,
              IF(history.notes IS NULL OR history.notes = '', '', CONCAT(' · ', history.notes))),
       history.changed_by, COALESCE(users.full_name COLLATE utf8mb4_general_ci, 'Sistem'), history.created_at
FROM application_status_histories AS history
INNER JOIN applications ON applications.id = history.application_id
INNER JOIN applicants ON applicants.id = applications.applicant_id
INNER JOIN vacancies ON vacancies.id = applications.vacancy_id
LEFT JOIN users ON users.id = history.changed_by
UNION ALL
SELECT 'assignment', CAST(history.id AS CHAR), history.action,
       applicants.full_name COLLATE utf8mb4_general_ci, COALESCE(applicants.email COLLATE utf8mb4_general_ci, '-'),
       CONCAT(COALESCE(from_team.name, 'Belum ditugaskan'), ' → ', COALESCE(to_team.name, 'Belum ditugaskan'),
              IF(history.notes IS NULL OR history.notes = '', '', CONCAT(' · ', history.notes))),
       history.changed_by, COALESCE(users.full_name COLLATE utf8mb4_general_ci, 'Sistem'), history.created_at
FROM applicant_assignment_histories AS history
INNER JOIN applicants ON applicants.id = history.applicant_id
LEFT JOIN hrd_teams AS from_team ON from_team.id = history.from_hrd_team_id
LEFT JOIN hrd_teams AS to_team ON to_team.id = history.to_hrd_team_id
LEFT JOIN users ON users.id = history.changed_by
UNION ALL
SELECT 'blacklist', CONCAT('applicant-', history.id), history.action,
       applicants.full_name COLLATE utf8mb4_general_ci, COALESCE(applicants.email COLLATE utf8mb4_general_ci, applicants.phone COLLATE utf8mb4_general_ci, '-'),
       CONCAT_WS(' · ', history.action_notes, history.reason_snapshot),
       history.changed_by, COALESCE(users.full_name COLLATE utf8mb4_general_ci, 'Sistem'), history.created_at
FROM applicant_blacklist_histories AS history
INNER JOIN applicant_blacklists AS blacklists ON blacklists.id = history.blacklist_id
INNER JOIN applicants ON applicants.id = blacklists.applicant_id
LEFT JOIN users ON users.id = history.changed_by
UNION ALL
SELECT 'blacklist', CONCAT('historical-', history.id), history.action,
       blacklists.full_name, COALESCE(blacklists.email, blacklists.phone, '-'), history.action_notes,
       history.changed_by, COALESCE(users.full_name COLLATE utf8mb4_general_ci, 'Sistem'), history.created_at
FROM historical_blacklist_histories AS history
INNER JOIN historical_blacklists AS blacklists ON blacklists.id = history.historical_blacklist_id
LEFT JOIN users ON users.id = history.changed_by
UNION ALL
SELECT 'schedule', CAST(history.id AS CHAR), history.action,
       applicants.full_name COLLATE utf8mb4_general_ci, CONCAT_WS(' · ', applications.application_number, vacancies.title),
       history.notes,
       history.changed_by, COALESCE(users.full_name COLLATE utf8mb4_general_ci, 'Sistem/Kandidat'), history.created_at
FROM recruitment_schedule_histories AS history
INNER JOIN recruitment_schedules AS schedules ON schedules.id = history.schedule_id
INNER JOIN applications ON applications.id = schedules.application_id
INNER JOIN applicants ON applicants.id = applications.applicant_id
INNER JOIN vacancies ON vacancies.id = applications.vacancy_id
LEFT JOIN users ON users.id = history.changed_by
UNION ALL
SELECT 'talent_pool', CAST(history.id AS CHAR), history.action_code,
       applicants.full_name COLLATE utf8mb4_general_ci,
       CONCAT_WS(' · ', COALESCE(vacancies.title, candidates.recommended_position), related_application.application_number),
       CONCAT_WS(' · ',
                 NULLIF(IF(history.previous_status IS NULL AND history.new_status IS NULL, '',
                           CONCAT(COALESCE(history.previous_status, 'Awal'), ' → ', COALESCE(history.new_status, '-'))), ''),
                 NULLIF(history.notes, '')),
       history.changed_by, COALESCE(users.full_name COLLATE utf8mb4_general_ci, 'Sistem'), history.created_at
FROM talent_pool_histories AS history
INNER JOIN talent_pool_candidates AS candidates ON candidates.id = history.talent_pool_candidate_id
INNER JOIN applicants ON applicants.id = candidates.applicant_id
LEFT JOIN vacancies ON vacancies.id = history.related_vacancy_id
LEFT JOIN applications AS related_application ON related_application.id = history.related_application_id
LEFT JOIN users ON users.id = history.changed_by
SQL;
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
