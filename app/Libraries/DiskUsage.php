<?php

namespace App\Libraries;

class DiskUsage
{
    public function read(string $path): ?array
    {
        if (! function_exists('disk_free_space') || ! function_exists('disk_total_space')) {
            return null;
        }

        // A restricted hosting environment may not expose filesystem statistics.
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        if ($free === false || $total === false || $total <= 0 || $free < 0 || $free > $total) {
            return null;
        }

        $used = $total - $free;
        $percent = $used / $total * 100;

        return [
            'free' => $this->formatBytes($free),
            'used' => $this->formatBytes($used),
            'total' => $this->formatBytes($total),
            'percent' => round($percent, 1),
            'status' => $free == 0 ? 'full' : ($percent >= 90 ? 'critical' : ($percent >= 80 ? 'warning' : 'healthy')),
        ];
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        $index = 0;
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return number_format($bytes, $index === 0 ? 0 : 2, ',', '.') . ' ' . $units[$index];
    }
}
