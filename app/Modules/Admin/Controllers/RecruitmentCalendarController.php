<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Services;
use DateInterval;
use DateTimeImmutable;

class RecruitmentCalendarController extends BaseController
{
    public function index(): string
    {
        $this->disableClientCaching();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $month = $this->month();
        $monthStart = new DateTimeImmutable($month . '-01 00:00:00');
        $monthEnd = $monthStart->modify('last day of this month')->setTime(23, 59, 59);
        $gridStart = $monthStart->modify('monday this week');
        $gridEnd = $monthEnd->modify('sunday this week');
        $events = array_merge(
            $this->scheduleEvents($userId, $gridStart, $gridEnd),
            $this->vacancyEvents($gridStart, $gridEnd)
        );
        usort($events, static fn (array $left, array $right): int => [$left['date'], $left['sort_time'], $left['priority']] <=> [$right['date'], $right['sort_time'], $right['priority']]);

        $eventsByDate = [];
        foreach ($events as $event) {
            $eventsByDate[$event['date']][] = $event;
        }

        $days = [];
        for ($cursor = $gridStart; $cursor <= $gridEnd; $cursor = $cursor->add(new DateInterval('P1D'))) {
            $date = $cursor->format('Y-m-d');
            $days[] = [
                'date' => $date,
                'day' => (int) $cursor->format('j'),
                'in_month' => $cursor->format('Y-m') === $month,
                'is_today' => $date === date('Y-m-d'),
                'events' => $eventsByDate[$date] ?? [],
            ];
        }

        $monthEvents = array_values(array_filter($events, static fn (array $event): bool => str_starts_with($event['date'], $month)));
        $upcoming = array_values(array_filter($events, static fn (array $event): bool => $event['timestamp'] >= time()));

        return view('admin/recruitment_calendar', [
            'auth' => $auth,
            'days' => $days,
            'month' => $month,
            'monthLabel' => $this->monthLabel($monthStart),
            'previousMonth' => $monthStart->sub(new DateInterval('P1M'))->format('Y-m'),
            'nextMonth' => $monthStart->add(new DateInterval('P1M'))->format('Y-m'),
            'summary' => [
                'interview' => $this->countType($monthEvents, 'interview'),
                'test' => $this->countType($monthEvents, 'test'),
                'vacancy' => $this->countType($monthEvents, 'vacancy_open') + $this->countType($monthEvents, 'vacancy_close'),
                'deadline' => $this->countType($monthEvents, 'candidate_deadline'),
            ],
            'upcoming' => array_slice($upcoming, 0, 8),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function scheduleEvents(int $userId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $builder = db_connect()->table('recruitment_schedules AS schedules')
            ->select('schedules.id, schedules.scheduled_at, schedules.confirmation_deadline_at, schedules.status, schedules.venue, applicants.id AS applicant_id, applicants.full_name, applicants.assigned_hrd_team_id, vacancies.title AS vacancy_title, stages.code AS stage_code, stages.name AS stage_name, pic.full_name AS pic_name')
            ->join('applications', 'applications.id = schedules.application_id')
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('vacancies', 'vacancies.id = applications.vacancy_id')
            ->join('recruitment_stages AS stages', 'stages.id = schedules.stage_id')
            ->join('users AS pic', 'pic.id = schedules.pic_user_id')
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null)
            ->where('schedules.status !=', 'cancelled')
            ->groupStart()
                ->groupStart()->where('schedules.scheduled_at >=', $start->format('Y-m-d H:i:s'))->where('schedules.scheduled_at <=', $end->format('Y-m-d H:i:s'))->groupEnd()
                ->orGroupStart()->where('schedules.confirmation_deadline_at >=', $start->format('Y-m-d H:i:s'))->where('schedules.confirmation_deadline_at <=', $end->format('Y-m-d H:i:s'))->groupEnd()
            ->groupEnd();
        if (! Services::authorization()->can($userId, 'schedules.view_all')) {
            $builder->where('schedules.pic_user_id', $userId);
        }

        $events = [];
        foreach ($builder->get()->getResultArray() as $row) {
            $isInterview = str_contains((string) $row['stage_code'], 'interview') || str_contains(mb_strtolower((string) $row['stage_name']), 'wawancara');
            if ($this->within((string) $row['scheduled_at'], $start, $end)) {
                $events[] = $this->event(
                    $isInterview ? 'interview' : 'test',
                    (string) $row['scheduled_at'],
                    (string) $row['stage_name'] . ' · ' . (string) $row['full_name'],
                    (string) $row['vacancy_title'],
                    'Interviewer: ' . (string) $row['pic_name'],
                    site_url('adminhrdmannakampus/pelamar/' . $row['applicant_id']) . '?source=division&team_id=' . (int) $row['assigned_hrd_team_id'],
                    $isInterview ? 10 : 20
                );
            }
            if ($this->within((string) $row['confirmation_deadline_at'], $start, $end)) {
                $events[] = $this->event(
                    'candidate_deadline',
                    (string) $row['confirmation_deadline_at'],
                    'Deadline konfirmasi · ' . (string) $row['full_name'],
                    (string) $row['stage_name'] . ' · ' . (string) $row['vacancy_title'],
                    'Interviewer: ' . (string) $row['pic_name'],
                    site_url('adminhrdmannakampus/pelamar/' . $row['applicant_id']) . '?source=division&team_id=' . (int) $row['assigned_hrd_team_id'],
                    30
                );
            }
        }

        return $events;
    }

    /** @return list<array<string, mixed>> */
    private function vacancyEvents(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $rows = db_connect()->table('vacancy_recruitment_periods AS periods')
            ->select('periods.id, periods.period_name, periods.opened_at, periods.closed_at, vacancies.title AS vacancy_title')
            ->join('vacancies', 'vacancies.id = periods.vacancy_id')
            ->where('periods.deleted_at', null)->where('vacancies.deleted_at', null)
            ->groupStart()
                ->groupStart()->where('periods.opened_at >=', $start->format('Y-m-d H:i:s'))->where('periods.opened_at <=', $end->format('Y-m-d H:i:s'))->groupEnd()
                ->orGroupStart()->where('periods.closed_at >=', $start->format('Y-m-d H:i:s'))->where('periods.closed_at <=', $end->format('Y-m-d H:i:s'))->groupEnd()
            ->groupEnd()->get()->getResultArray();

        $events = [];
        foreach ($rows as $row) {
            if ($this->within((string) $row['opened_at'], $start, $end)) {
                $events[] = $this->event('vacancy_open', (string) $row['opened_at'], 'Lowongan dibuka · ' . (string) $row['vacancy_title'], (string) $row['period_name'], 'Periode penerimaan lamaran dimulai', site_url('adminhrdmannakampus/sesi-lowongan'), 40);
            }
            if ($this->within((string) $row['closed_at'], $start, $end)) {
                $events[] = $this->event('vacancy_close', (string) $row['closed_at'], 'Lowongan ditutup · ' . (string) $row['vacancy_title'], (string) $row['period_name'], 'Batas akhir penerimaan lamaran', site_url('adminhrdmannakampus/sesi-lowongan'), 50);
            }
        }

        return $events;
    }

    /** @return array<string, mixed> */
    private function event(string $type, string $dateTime, string $title, string $subtitle, string $meta, string $url, int $priority): array
    {
        $timestamp = strtotime($dateTime) ?: 0;

        return ['type' => $type, 'date' => date('Y-m-d', $timestamp), 'time' => date('H:i', $timestamp), 'sort_time' => date('H:i:s', $timestamp), 'timestamp' => $timestamp, 'title' => $title, 'subtitle' => $subtitle, 'meta' => $meta, 'url' => $url, 'priority' => $priority];
    }

    private function month(): string
    {
        $month = trim((string) $this->request->getGet('month'));

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1 ? $month : date('Y-m');
    }

    private function within(string $value, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        $timestamp = strtotime($value);

        return $timestamp !== false && $timestamp >= $start->getTimestamp() && $timestamp <= $end->getTimestamp();
    }

    /** @param list<array<string, mixed>> $events */
    private function countType(array $events, string $type): int
    {
        return count(array_filter($events, static fn (array $event): bool => $event['type'] === $type));
    }

    private function monthLabel(DateTimeImmutable $month): string
    {
        $names = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $names[(int) $month->format('n')] . ' ' . $month->format('Y');
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
