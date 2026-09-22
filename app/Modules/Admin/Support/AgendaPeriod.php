<?php

namespace App\Modules\Admin\Support;

use DateInterval;
use DateTimeImmutable;

final class AgendaPeriod
{
    private DateTimeImmutable $selected;
    private DateTimeImmutable $start;
    private DateTimeImmutable $end;
    private string $view;

    public function __construct(string $date, string $view)
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $this->selected = $parsed !== false && $parsed->format('Y-m-d') === $date
            ? $parsed : new DateTimeImmutable('today');
        $this->view = in_array($view, ['day', 'week', 'month'], true) ? $view : 'month';
        if ($this->view === 'week') {
            $this->start = $this->selected->modify('monday this week');
            $this->end = $this->start->add(new DateInterval('P6D'));
        } elseif ($this->view === 'month') {
            $monthStart = $this->selected->modify('first day of this month');
            $this->start = $monthStart->modify('monday this week');
            $this->end = $monthStart->modify('last day of this month')->modify('sunday this week');
        } else {
            $this->start = $this->selected;
            $this->end = $this->selected;
        }
    }

    public function view(): string
    {
        return $this->view;
    }

    public function selectedDate(): string
    {
        return $this->selected->format('Y-m-d');
    }

    public function from(): string
    {
        return $this->start->format('Y-m-d 00:00:00');
    }

    public function until(): string
    {
        return $this->end->format('Y-m-d 23:59:59');
    }

    public function previous(): string
    {
        return match ($this->view) {
            'week' => $this->start->sub(new DateInterval('P7D'))->format('Y-m-d'),
            'month' => $this->selected->modify('first day of this month')->sub(new DateInterval('P1M'))->format('Y-m-d'),
            default => $this->selected->sub(new DateInterval('P1D'))->format('Y-m-d'),
        };
    }

    public function next(): string
    {
        return match ($this->view) {
            'week' => $this->start->add(new DateInterval('P7D'))->format('Y-m-d'),
            'month' => $this->selected->modify('first day of this month')->add(new DateInterval('P1M'))->format('Y-m-d'),
            default => $this->selected->add(new DateInterval('P1D'))->format('Y-m-d'),
        };
    }

    public function label(): string
    {
        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if ($this->view === 'month') {
            return $months[(int) $this->selected->format('n')] . ' ' . $this->selected->format('Y');
        }
        if ($this->view === 'week') {
            return $this->start->format('d/m/Y') . ' – ' . $this->end->format('d/m/Y');
        }
        $days = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return $days[(int) $this->selected->format('N')] . ', ' . $this->selected->format('j') . ' '
            . $months[(int) $this->selected->format('n')] . ' ' . $this->selected->format('Y');
    }

    /** @return list<array{date: string, day: int, in_month: bool, is_today: bool}> */
    public function days(): array
    {
        $days = [];
        for ($cursor = $this->start; $cursor <= $this->end; $cursor = $cursor->add(new DateInterval('P1D'))) {
            $date = $cursor->format('Y-m-d');
            $days[] = [
                'date' => $date,
                'day' => (int) $cursor->format('j'),
                'in_month' => $cursor->format('Y-m') === $this->selected->format('Y-m'),
                'is_today' => $date === date('Y-m-d'),
            ];
        }

        return $days;
    }
}
